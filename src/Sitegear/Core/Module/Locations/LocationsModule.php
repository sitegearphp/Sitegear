<?php
/*!
 * This file is a part of Sitegear.
 * Copyright (c) Ben New, Leftclick.com.au
 * See the LICENSE and README files in the main source directory for details.
 * http://sitegear.org/
 */

namespace Sitegear\Core\Module\Locations;

use Sitegear\Base\Resources\ResourceLocations;
use Sitegear\Base\View\ViewInterface;
use Sitegear\Core\Module\AbstractCoreModule;
use Sitegear\Core\Module\Locations\Repository\ItemRepository;
use Sitegear\Util\TokenUtilities;
use Sitegear\Util\LoggerRegistry;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Doctrine\ORM\NoResultException;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * LocationsModule allows display and management of geographical locations.  By default, it requires GoogleModule for
 * map generation, however this can be overridden using custom view scripts.
 *
 * @method \Sitegear\Core\Engine\Engine getEngine()
 */
class LocationsModule extends AbstractCoreModule {

	//-- Constants --------------------

	/**
	 * Alias to use for this module's entity namespace.
	 */
	const ENTITY_ALIAS = 'Locations';

	//-- ModuleInterface Methods --------------------

	/**
	 * {@inheritDoc}
	 */
	public function getDisplayName() {
		return 'Locations Management';
	}

	/**
	 * {@inheritDoc}
	 */
	public function start() {
		parent::start();
		// Register "location-search" form.
		$filename = $this->config('location-search-form.filename');
		$this->getEngine()->forms()->registerFormDefinitionFilePath($this->config('location-search-form.key'), array(
			$this->getEngine()->getSiteInfo()->getSitePath(ResourceLocations::RESOURCE_LOCATION_SITE, $this, $filename),
			$this->getEngine()->getSiteInfo()->getSitePath(ResourceLocations::RESOURCE_LOCATION_MODULE, $this, $filename)
		));
		// Setup Doctrine.
		$this->getEngine()->doctrine()->getEntityManager()->getConfiguration()->addEntityNamespace(self::ENTITY_ALIAS, '\\Sitegear\\Core\\Module\\Locations\\Model');
	}

	//-- AbstractUrlMountableModule Methods --------------------

	/**
	 * {@inheritDoc}
	 */
	protected function buildNavigationData($mode) {
		$data = $this->buildNavigationDataImpl($mode, intval($this->config('navigation.max-depth')));
		if ($mode === self::NAVIGATION_DATA_MODE_EXPANDED) {
			foreach ($this->getRepository('Item')->findByActive(true) as $item) {
				/** @var \Sitegear\Core\Module\Locations\Model\Item $item */
				$data[] = array(
					'url' => $this->getRouteUrl('item', $item->getUrlPath()),
					'label' => $item->getName()
				);
			}
		}
		return $data;
	}

	//-- Page Controller Methods --------------------

	/**
	 * Display the top-level landing page for the locations module.
	 *
	 * @param \Sitegear\Base\View\ViewInterface $view
	 */
	public function indexController(ViewInterface $view) {
		LoggerRegistry::debug('LocationsModule::indexController()');
		$this->applyConfigToView('page.index', $view);
		$view['regions'] = new ArrayCollection($this->getRepository('Region')->findByParent(null));
	}

	/**
	 * Display a landing page for a region.
	 *
	 * @param \Sitegear\Base\View\ViewInterface $view
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 */
	public function regionController(ViewInterface $view, Request $request) {
		LoggerRegistry::debug('LocationsModule::regionController()');
		$this->applyConfigToView('page.region', $view);
		/** @var \Sitegear\Core\Module\Locations\Model\Region $region */
		$region = $this->getRepository('Region')->findOneByUrlPath($request->attributes->get('slug'));
		$view['region'] = $region;
		$view['regions'] = $region->getChildren();
		$view['items'] = $region->getItems();
	}

	/**
	 * Display the details page for an individual location item.
	 *
	 * @param \Sitegear\Base\View\ViewInterface $view
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function itemController(ViewInterface $view, Request $request) {
		LoggerRegistry::debug('LocationsModule::itemController()');
		$this->applyConfigToView('page.item', $view);
		try {
			$view['item'] = $this->getRepository('Item')->findOneBy(array( 'urlPath' => $request->attributes->get('slug'), 'active' => true ));
		} catch (NoResultException $e) {
			throw new NotFoundHttpException('The requested product is not available.', $e);
		}
	}

	/**
	 * Perform a search for a given (named) location and display results within a specified radius.
	 *
	 * TODO Another parameter, region, to restrict results to that region (and its children)
	 *
	 * @param \Sitegear\Base\View\ViewInterface $view
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 *
	 * @return RedirectResponse|null
	 *
	 * @throws \InvalidArgumentException
	 */
	public function searchController(ViewInterface $view, Request $request) {
		LoggerRegistry::debug('LocationsModule::itemController()');
		$this->applyConfigToView('page.search', $view);
		$query = $request->query->get('query');
		$radius = $request->query->get('radius');
		if (strlen($query) === 0) {
			return new RedirectResponse($request->getUriForPath('/' . $this->getRouteUrl('index')));
		}
		if (!is_numeric($radius)) {
			throw new \InvalidArgumentException(sprintf('LocationsModule received invalid radius in search; must be numeric, "%s" received', $radius));
		}
		$view['query'] = $query = TokenUtilities::replaceTokens($this->config('search.query-mask'), array( 'query' => $query ));
		$view['radius'] = $radius = intval($radius);
		$location = $this->getEngine()->google()->geocodeLocation($query);
		/** @var ItemRepository $itemRepository */
		$itemRepository = $this->getRepository('Item');
		$view['items'] = new ArrayCollection($radius > 0 ? $itemRepository->findInRadius($location, $radius) : array());
		$view['results-description'] = TokenUtilities::replaceTokens($view['results-description-format'], array( 'query' => $query, 'radius' => number_format($radius) ));
		$view['no-items'] = 'no-items-search';
		return null;
	}

	//-- Component Controller Methods --------------------

	/**
	 * The location search form.
	 *
	 * @param ViewInterface $view
	 * @param string|null $query Previous query, to populate the value.
	 * @param string|null $radius Previous radius selection, to populate the selected option.
	 */
	public function searchFormComponent(ViewInterface $view, $query=null, $radius=null) {
		LoggerRegistry::debug('LocationsModule::searchFormComponent()');
		$this->applyConfigToView('component.search-form', $view);
		$view['action-url'] = $this->getRouteUrl('search');
		if (!is_null($query)) {
			$view['query'] = $query;
		}
		if (!is_null($radius)) {
			$view['radius'] = $radius;
		}
	}

	//-- Internal Methods --------------------

	/**
	 * Recursive method for navigation generation.
	 *
	 * @param int $mode
	 * @param int $maxDepth
	 * @param null|int|\Sitegear\Core\Module\Locations\Model\Region $parent
	 *
	 * @return array
	 */
	private function buildNavigationDataImpl($mode, $maxDepth, $parent=null) {
		$result = array();
		foreach ($this->getRepository('Region')->findByParent($parent) as $region) {
			/** @var \Sitegear\Core\Module\Locations\Model\Region $region */
			$tooltip = TokenUtilities::replaceTokens(
				$this->config('navigation.tooltip-format'),
				array(
					'regionName' => $region->getName()
				)
			);
			$regionResult = array(
				'url' => $this->getRouteUrl('region', $region->getUrlPath()),
				'label' => $region->getName(),
				'tooltip' => $tooltip
			);
			if ($mode === self::NAVIGATION_DATA_MODE_EXPANDED || $maxDepth !== 1) { // >1 means more levels before the limit is reached, <=0 means no limit
				$subRegions = $this->buildNavigationDataImpl($mode, max(0, $maxDepth - 1), $region);
				if (!empty($subRegions)) {
					$regionResult['children'] = $subRegions;
				}
			}
			$result[] = $regionResult;
		}
		return $result;
	}

	/**
	 * @param string $entity
	 *
	 * @return \Doctrine\ORM\EntityRepository
	 */
	private function getRepository($entity) {
		return $this->getEngine()->doctrine()->getEntityManager()->getRepository(sprintf('%s:%s', self::ENTITY_ALIAS, $entity));
	}

}