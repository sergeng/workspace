<?php
namespace OCA\Workspace\Service;

use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Service\WorkspaceService;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IUserSession;

Class UserService {

	/** @var $IGroupManager */
	private $groupManager;

	/** @var $IUserManager */
	private $userManager;

	/** @var IUserSession */
	private $userSession;

	/** @var $WorkspaceService */
	private $workspaceService;

	public function __construct(
		IGroupManager $group,
		IUserManager $userManager,
		IUserSession $userSession,
		WorkspaceService $workspaceService) {

		$this->groupManager = $group;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->workspaceService = $workspaceService;

	}

	/**
	 * Returns a list of users whose name matches $term
	 *
	 * @param string $term
	 * @param string $spaceId
	 *
	 * @return array
	 */
	public function autoComplete(string $term, string $spaceId) {
		// lookup users
		$term = $term === '*' ? '' : $term;
		$users = $this->userManager->searchDisplayName($term, 50);

		// transform in a format suitable for the app
		$data = [];
		$space = $this->workspaceService->get($spaceId);
		foreach($users as $user) {
			$data[] = $this->formatUser($user, $space, 'user');
		}

		// return info
		return $data;
	}

	/**
	 *
	 * Given a IUser, returns an array containing all the user information
	 * needed for the frontend
	 *
	 * @param IUser $user
	 * @param array $space
	 * @param string $role
	 *
	 * @return array|null
	 *
	 */

	public function formatUser($user, $space, $role) {
	
		if (is_null($user)) {
			return;
		}

		// Gets the workspace subgroups the user is member of
		$groups = [];
		foreach($this->groupManager->getUserGroups($user) as $group) {
			if (in_array($group->getGID(), array_keys($space['groups']))) {
				array_push($groups, $group->getGID());
			}
		}

		// Returns a user that is valid for the frontend
		return array(
			'uid' => $user->getUID(),
			'name' => $user->getDisplayName(),
			'email' => $user->getEmailAddress(),
			'subtitle' => $user->getEmailAddress(),
			'groups' => $groups,
			'role' => $role
		);

	}

	/**
	 * @return boolean true if user is general admin, false otherwise
	*/
	public function isUserGeneralAdmin() {
		if ($this->groupManager->isInGroup($this->userSession->getUser()->getUID(), Application::GENERAL_MANAGER)) {
			return true;
		}
		return false;
	}

	/**
	 * @return boolean true if user is a space manager, false otherwise
	*/
	public function isSpaceManager() {
		$workspaceAdminGroups = $this->groupManager->search(Application::ESPACE_MANAGER_01);
		foreach($workspaceAdminGroups as $group) {
			if ($this->groupManager->isInGroup($this->userSession->getUser()->getUID(), $group->getGID())) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param string $id The space id
	 * @return boolean true if user is space manager of the specified workspace, false otherwise
	*/
	public function isSpaceManagerOfSpace($id) {
		// Get space details
		$space = $this->workspaceService->get($id);

		$workspaceAdminGroup = $this->groupManager->search(Application::ESPACE_MANAGER_01 . $space['space_name']);

		if (count($workspaceAdminGroup) == 0) {
			// TODO Log error
			return false;
		}

		if (count($workspaceAdminGroup) > 1) {
			// TODO Several group match the space name, we need to find the good one
		}

		if ($this->groupManager->isInGroup($this->userSession->getUser()->getUID(), $workspaceAdminGroup[0]->getGID())) {
			return true;
		}
		return false;
	}

}
