<?php
/**
 * Groups Controller
 *
 * @copyright   Copyright 2012, Passbolt.com
 * @license     http://www.passbolt.com/license
 * @package     app.Controller.GroupsController
 * @since       version 2.13.6
 */
class GroupsController extends AppController {

	public $helpers = array('PassboltAuth');

	/**
	 * Index
	 *
	 * @access public
	 */
	public function index() {
		$keywords = isset($this->request->query['keywords']) ? $this->request->query['keywords'] : '';

		$data = array();
		// if keywords provided build the model request with
		if (!empty($keywords)) {
			$data['keywords'] = $keywords;
		}

		$o = $this->Group->getFindOptions('index', User::get('Role.name'), $data);
		$returnVal = $this->Group->find('all', $o);
		if (empty($returnVal)) {
			$this->Message->notice(__('There is no group to display'));
			return;
		}

		$this->Message->success();
		$this->set('data', $returnVal);
	}

	/**
	 * View
	 *
	 * @param $id UUID of the user
	 *
	 * @access public
	 */
	public function view($id = null) {
		// check if the id is provided
		if (!isset($id)) {
			$this->Message->error(__('The group id is missing'));
		return;
		}
		// check if the id is valid
		if (!Common::isUuid($id)) {
			$this->Message->error(__('The group id invalid'));
			return;
		}

		$data = array('Group.id' => $id);
		$o = $this->Group->getFindOptions('view', User::get('Role.name'), $data);
		$group = $this->Group->find('first', $o);
		if (!$group) {
			$this->Message->error(__('The group does not exist'));
			return;
		}
		$this->set('data', $group);
		$this->Message->success();
	}

	/**
	 * group add entry point
	 */
	public function add() {

		// First of all, check if the user is an administrator
		if (User::get('Role.name') != Role::ADMIN) {
			$this->Message->error(__('You are not allowed to access this entry point'));

			return;
		}

		// check the HTTP request method
		if (!$this->request->is('post')) {
			$this->Message->error(__('Invalid request method, should be POST'));

			return;
		}
		// check if data was provided
		if (!isset($this->request->data['Group'])) {
			$this->Message->error(__('No data were provided'));

			return;
		}

		// set the data for validation and save
		$groupData = $this->request->data;
		$this->Group->set($groupData);

		$fields = $this->Group->getFindFields('save', User::get('Role.name'));

		// check if the data is valid
		if (!$this->Group->validates()) {
			$this->Message->error(__('Could not validate group data'));

			return;
		}

		$this->Group->begin();
		$group= $this->Group->save($groupData, false, $fields['fields']);

		if ($group == false) {
			$this->Group->rollback();
			$this->Message->error(__('The group could not be saved'));

			return;
		}
		$this->Group->commit();
		$data = array('Group.id' => $this->Group->id);
		$options = $this->Group->getFindOptions('view', User::get('Role.name'), $data);
		$group = $this->Group->find('first', $options);

		$this->Message->success(__("The group has been saved successfully"));
		$this->set('data', $group);

		return;
	}

	/**
	 * edit entry point for users
	 *
	 * @param uuid $id the id of the user we want to edit
	 */
	public function edit($id = null) {
		// First of all, check if the user is an administrator
		if (User::get('Role.name') != Role::ADMIN) {
			$this->Message->error(__('You are not allowed to access this entry point'));

			return;
		}

		// check the HTTP request method
		if (!$this->request->is('put')) {
			$this->Message->error(__('Invalid request method, should be PUT'));

			return;
		}

		// check if data was provided
		if (!isset($this->request->data['Group'])) {
			$this->Message->error(__('No data were provided'));

			return;
		}

		// set the data for validation and save
		$groupData = $this->request->data;

		if (isset($groupData['Group'])) {

			$this->Group->id = $id;

			// check if the id is valid
			if (!Common::isUuid($id)) {
				$this->Message->error(__('The group id invalid'));

				return;
			}
			// get the resource id
			$resource = $this->Group->findById($id);
			if (!$resource) {
				$this->Message->error(__('The group doesn\'t exist'));

				return;
			}

			$this->Group->set($groupData);
			if (!$this->Group->validates()) {
				$this->Message->error(__('Could not validate group'));

				return;
			}

			$fields = $this->Group->getFindFields('edit', User::get('Role.name'));
			$this->Group->begin();
			$save = $this->Group->save($groupData, false, $fields['fields']);
			if (!$save) {
				$this->Group->rollback();
				$this->Message->error(__('The user could not be updated'));

				return;
			}
			$this->Group->commit();

			$data = array('Group.id' => $this->Group->id);
			$options = $this->Group->getFindOptions('view', User::get('Role.name'), $data);
			$group = $this->Group->find('first', $options);

			$this->Message->success(__("The group has been updated successfully"));
			$this->set('data', $group);

			return;
		}
	}

	/**
	 * Delete a user
	 *
	 * @param uuid id the id of the user to delete
	 */
	public function delete($id = null) {
		// First of all, check if the user is an administrator
		if (User::get('Role.name') != Role::ADMIN) {
			$this->Message->error(__('You are not allowed to access this entry point'));

			return;
		}

		// check if the category id is provided
		if (!isset($id)) {
			$this->Message->error(__('The group id is missing'));

			return;
		}
		// check if the id is valid
		if (!Common::isUuid($id)) {
			$this->Message->error(__('The group id invalid'));

			return;
		}
		$group = $this->Group->findById($id);
		if (!$group) {
			$this->Message->error(__('The group doesn\'t exist'));

			return;
		}
		$this->Group->id = $id;
		$group['Group']['deleted'] = true;

		$fields = $this->Group->getFindFields('delete', User::get('Role.name'));
		$this->Group->begin();
		if (!$this->Group->save($group, true, $fields['fields'])) {
			$this->Group->rollback();
			$this->Message->error(__('Error while deleting group'));

			return;
		}
		$this->Group->commit();
		$this->Message->success(__('The group was sucessfully deleted'));
	}


}