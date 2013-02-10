<?php
/*!
 * This file is a part of Sitegear.
 * Copyright (c) Ben New, Leftclick.com.au
 * See the LICENSE and README files in the main source directory for details.
 * http://sitegear.org/
 */

namespace Sitegear\Ext\Module\Products\Entities;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity(repositoryClass="Sitegear\Ext\Module\Products\ProductsRepository")
 * @Table(name="products_item")
 */
class Item {

	//-- Attributes --------------------

	/**
	 * @var integer
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 */
	private $id;

	/**
	 * @var string
	 * @Column(type="string", nullable=false, unique=true)
	 */
	private $urlPath;

	/**
	 * @var string
	 * @Column(type="string", nullable=false)
	 */
	private $name;

	/**
	 * @var boolean
	 * @Column(type="boolean")
	 */
	private $active;

	/**
	 * @var boolean
	 * @Column(type="boolean")
	 */
	private $featured;

	/**
	 * @var \DateTime
	 * @Column(type="datetime", nullable=false)
	 * @Timestampable(on="create")
	 */
	private $dateCreated;

	/**
	 * @var \DateTime
	 * @Column(type="datetime", nullable=true)
	 * @Timestampable(on="update")
	 */
	private $dateModified;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 * @OneToMany(targetEntity="CategoryAssignment", mappedBy="item")
	 * @OrderBy({"displaySequence"="ASC"})
	 */
	private $categoryAssignments;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 * @OneToMany(targetEntity="Relationship", mappedBy="item")
	 */
	private $relationships;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 * @OneToMany(targetEntity="Relationship", mappedBy="item")
	 */
	private $inverseRelationships;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 * @OneToMany(targetEntity="AttributeAssignment", mappedBy="item")
	 */
	private $attributeAssignments;

	/**
	 * @var \Doctrine\Common\Collections\Collection
	 * @OneToMany(targetEntity="Specification", mappedBy="item")
	 */
	private $specifications;

	//-- Constructor --------------------

	public function __construct() {
		$this->categoryAssignments = new ArrayCollection();
		$this->relationships = new ArrayCollection();
		$this->inverseRelationships = new ArrayCollection();
		$this->attributeAssignments = new ArrayCollection();
		$this->specifications = new ArrayCollection();
	}

	//-- Accessor Methods --------------------

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @param boolean $active
	 */
	public function setActive($active) {
		$this->active = $active;
	}

	/**
	 * @return boolean
	 */
	public function getActive() {
		return $this->active;
	}

	/**
	 * @return boolean
	 */
	public function getFeatured() {
		return $this->featured;
	}

	/**
	 * @param boolean $featured
	 */
	public function setFeatured($featured) {
		$this->featured = $featured;
	}

	/**
	 * @return \DateTime
	 */
	public function getDateCreated() {
		return $this->dateCreated;
	}

	/**
	 * @return \DateTime
	 */
	public function getDateModified() {
		return $this->dateModified;
	}

	/**
	 * @param string $longName
	 */
	public function setName($longName) {
		$this->name = $longName;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param string $urlPath
	 */
	public function setUrlPath($urlPath) {
		$this->urlPath = $urlPath;
	}

	/**
	 * @return string
	 */
	public function getUrlPath() {
		return $this->urlPath;
	}

	/**
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getRelationships() {
		return $this->relationships;
	}

	/**
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getInverseRelationships() {
		return $this->inverseRelationships;
	}

	/**
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getCategoryAssignments() {
		return $this->categoryAssignments;
	}

	/**
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getSpecifications() {
		return $this->specifications;
	}

	/**
	 * @return \Doctrine\Common\Collections\Collection
	 */
	public function getAttributeAssignments() {
		return $this->attributeAssignments;
	}

}
