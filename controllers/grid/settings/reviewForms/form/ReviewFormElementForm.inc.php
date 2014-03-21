<?php

/**
 * @file classes/manager/form/ReviewFormElementForm.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormElementForm
 * @ingroup controllers_grid_settings_reviewForms_form
 * @see ReviewFormElement
 *
 * @brief Form for creating and modifying review form elements.
 *
 */

import('lib.pkp.classes.db.DBDataXMLParser');
import('lib.pkp.classes.form.Form');

class ReviewFormElementForm extends Form {

	/** @var $reviewFormId int The ID of the review form being edited */
	var $reviewFormId;

	/** @var $reviewFormElementId int The ID of the review form element being edited */
	var $reviewFormElementId;

	/**
	 * Constructor.
	 * @param $reviewFormId int
	 * @param $reviewFormElementId int
	 */
	function ReviewFormElementForm($reviewFormId, $reviewFormElementId = null) {
		parent::Form('manager/reviewForms/reviewFormElementForm.tpl');

		$this->reviewFormId = $reviewFormId;
		$this->reviewFormElementId = $reviewFormElementId;

		// Validation checks for this form
		$this->addCheck(new FormValidatorLocale($this, 'question', 'required', 'manager.reviewFormElements.form.questionRequired'));
		$this->addCheck(new FormValidator($this, 'elementType', 'required', 'manager.reviewFormElements.form.elementTypeRequired'));
		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Get the names of fields for which localized data is allowed.
	 * @return array
	 */
	function getLocaleFieldNames() {
		$reviewFormElementDao = DAORegistry::getDAO('ReviewFormElementDAO');
		return $reviewFormElementDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function fetch($args, $request) {
		$json = new JSONMessage();

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('reviewFormId', $this->reviewFormId);
		$templateMgr->assign('reviewFormElementId', $this->reviewFormElementId);
		import('lib.pkp.classes.reviewForm.ReviewFormElement');
		$templateMgr->assign('multipleResponsesElementTypes', ReviewFormElement::getMultipleResponsesElementTypes());
		// in order to be able to search for an element in the array in the javascript function 'togglePossibleResponses':
		$templateMgr->assign('multipleResponsesElementTypesString', ';'.implode(';', ReviewFormElement::getMultipleResponsesElementTypes()).';');
		$templateMgr->assign('reviewFormElementTypeOptions', ReviewFormElement::getReviewFormElementTypeOptions());

		return parent::fetch($request);
	}

	/**
	 * Initialize form data from current review form.
	 * @param $request PKPRequest
	 */
	function initData($request) {
		if ($this->reviewFormElementId) {
			$context = $request->getContext();
			$reviewFormElementDao = DAORegistry::getDAO('ReviewFormElementDAO');
			$reviewFormElement = $reviewFormElementDao->getById($this->reviewFormElementId, Application::getContextAssocType(), $context->getId());

			if ($reviewFormElement == null) {
				$this->_data = array(
					'included' => 1
				);
			} else {
				$this->_data = array(
					'question' => $reviewFormElement->getQuestion(null), // Localized
					'required' => $reviewFormElement->getRequired(),
					'included' => $reviewFormElement->getIncluded(),

					'elementType' => $reviewFormElement->getElementType(),
					'possibleResponses' => $reviewFormElement->getPossibleResponses(null) //Localized
				);
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('question', 'required', 'included', 'elementType', 'possibleResponses'));
	}

	/**
	 * Save review form element.
	 * @param $request PKPRequest
	 * @return int Review form element ID
	 */
	function execute($request) {
		$reviewFormElementDao = DAORegistry::getDAO('ReviewFormElementDAO');

		if ($this->reviewFormElementId != null) {
			$context = $request->getContext();
			$reviewFormElement = $reviewFormElementDao->getById($this->reviewFormElementId);
			$reviewFormDao = DAORegistry::getDAO('ReviewFormDAO');
			$reviewForm = $reviewFormDao->getById($reviewFormElement->getReviewFormId(), $context->getId());
			if (!$reviewForm) fatalError('Invalid review form element ID!');
		}

		if (!isset($reviewFormElement)) {
			$reviewFormElement = new ReviewFormElement();
			$reviewFormElement->setReviewFormId($this->reviewFormId);
			$reviewFormElement->setSequence(REALLY_BIG_NUMBER);
		}

		$reviewFormElement->setQuestion($this->getData('question'), null); // Localized
		$reviewFormElement->setRequired($this->getData('required') ? 1 : 0);
		$reviewFormElement->setIncluded($this->getData('included') ? 1 : 0);
		$reviewFormElement->setElementType($this->getData('elementType'));

		if (in_array($this->getData('elementType'), ReviewFormElement::getMultipleResponsesElementTypes())) {
			$reviewFormElement->setPossibleResponses($this->getData('possibleResponses'), null); // Localized
		} else {
			$reviewFormElement->setPossibleResponses(null, null);
		}

		if ($reviewFormElement->getId() != null) {
			$reviewFormElementDao->deleteSetting($reviewFormElement->getId(), 'possibleResponses');
			$reviewFormElementDao->updateObject($reviewFormElement);
			$this->reviewFormElementId = $reviewFormElement->getId();
		} else {
			$this->reviewFormElementId = $reviewFormElementDao->insertObject($reviewFormElement);
			$reviewFormElementDao->resequenceReviewFormElements($this->reviewFormId);
		}

		return $this->reviewFormElementId;
	}
}

?>