<!--
  @copyright Copyright (c) 2017 Arawa

  @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
  @author 2021 Cyrille Bollu <cyrille@bollu.be>

  @license GNU AGPL version 3 or any later version

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as
  published by the Free Software Foundation, either version 3 of the
  License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
-->

<template>
	<div class="content-select-groupfolders">
		<div class="header-select-groupfolders">
			<div class="header-select-groupfolders-title">
				<h1 class="title-select-groupfolders">
					{{ t('workspace', 'Select groupfolders to convert in workspace') }}
				</h1>
			</div>
			<NcActions class="action-close">
				<NcActionButton
					icon="icon-close"
					@click="$emit('close')" />
			</NcActions>
		</div>
		<div class="select-groupfolders-list">
			<div
				v-for="(groupfolder) in $store.state.groupfolders"
				:key="groupfolder.mount_point"
				class="groupfolder-entry">
				<div class="groupfolder-name">
					<span>{{ groupfolder.mount_point }}</span>
				</div>
				<input
					:id="groupfolder.name"
					v-model="allSelectedGroupfoldersId"
					type="checkbox"
					:value="groupfolder.mount_point"
					class="convert-space">
			</div>
		</div>
		<div class="select-groupfolders-actions">
			<button @click="convertGroupfoldersToSpace()">
				{{ t('workspace', 'Convert in spaces') }}
			</button>
		</div>
	</div>
</template>

<script>

import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import { get, getAll, enableAcl, addGroupToGroupfolder, addGroupToManageACLForGroupfolder, removeGroupToManageACLForGroupfolder } from './services/groupfoldersService.js'
import { createSpace, isSpaceManagers, isSpaceUsers, transferUsersToUserGroup } from './services/spaceService.js'

export default {
	name: 'SelectGroupfolders',
	components: {
		NcActions,
		NcActionButton,
	},
	data() {
		return {
			allSelectedGroupfoldersId: [],
		}
	},
	created() {
		this.getGroupfolders()
			.then(resultat => {
				resultat.forEach(groupfolder => {
					this.$store.dispatch('updateGroupfolders', {
						groupfolder,
					})
				})
			})
			.catch(error => {
				console.error('Error to get groupfolders to convert in space', error)
			})
	},
	methods: {
		// get groupfolders whithout spaces.
		async getGroupfolders() {
			const groupfolders = await getAll()

			// get all spaces and "convert" in the form of Array.
			const allSpaces = []
			const spaces = this.$store.state.spaces
			const spacesKey = Object.keys(spaces)
			for (const spaceKey of spacesKey) {
				allSpaces.push(spaces[spaceKey])
			}

			const groupfoldersIdFromSpaces = allSpaces.map(space => space.groupfolderId.toString())

			// get Keys from groupfolders
			// example: [ "592", "593", "594" ]
			const groupfoldersKey = Object.keys(groupfolders)

			// Get the difference between spaces and groupfolders to get groupfoders which aren't spaces
			const groupfoldersKeysWhithoutSpace = groupfoldersKey.filter(groupfolderKey => !groupfoldersIdFromSpaces.includes(groupfolderKey))

			// Build a Groupfolders' array
			const groupfoldersWhithoutSpace = []
			for (const key of groupfoldersKeysWhithoutSpace) {
				groupfoldersWhithoutSpace.push(groupfolders[key])
			}

			return groupfoldersWhithoutSpace
		},
		async convertGroupfoldersToSpace() {
			const groupfolders = this.$store.state.groupfolders
			this.$emit('close')

			const groupfoldersSelected = { }
			this.allSelectedGroupfoldersId.forEach(mountPoint => {
				groupfoldersSelected[mountPoint] = groupfolders[mountPoint]
			})

			// convert here now
			for (const mountPoint in groupfoldersSelected) {
				await enableAcl(groupfoldersSelected[mountPoint].id)

				const space = await createSpace(mountPoint, groupfoldersSelected[mountPoint].id)

				// Add groups to groupfolder
				const GROUPS = Object.keys(space.groups)
				const spaceManagerGID = GROUPS.find(isSpaceManagers)
				const spaceUserGID = GROUPS.find(isSpaceUsers)

				await addGroupToGroupfolder(space.folder_id, spaceManagerGID)
				await addGroupToGroupfolder(space.folder_id, spaceUserGID)

				const groupfolder = await get(groupfoldersSelected[mountPoint].id, this)
				const groupstransferoUserGroup = await transferUsersToUserGroup(space.id_space, groupfolder)

				await addGroupToManageACLForGroupfolder(space.folder_id, spaceManagerGID, this)

				const GidGroupsFromACL = groupfoldersSelected[mountPoint].manage.map(group => group.id)
				await GidGroupsFromACL.forEach(gid => {
					removeGroupToManageACLForGroupfolder(space.folder_id, gid)
				})

				// Define the quota
				let quota = ''
				if (groupfoldersSelected[mountPoint].quota === '-3') {
					quota = t('workspace', 'unlimited')
				} else {
					quota = groupfoldersSelected[mountPoint].quota
				}

				this.$store.commit('addSpace', {
					color: space.color,
					groups: groupstransferoUserGroup.groups,
					isOpen: false,
					id: space.id_space,
					groupfolderId: space.folder_id,
					name: space.space_name,
					quota,
					users: groupstransferoUserGroup.users,
				})

			}
		},
	},
}

</script>

<style>

.modal-container {
	display: flex !important;
	min-height: 520px !important;
	max-height: 520px !important;
}

.content-select-groupfolders {
	display: flex;
	flex-grow: 1;
	flex-direction: column;
	align-items: center;
	margin: 10px;
}

.header-select-groupfolders {
	display: flex;
	flex-direction: row;
	align-items: center;
	width: 100%;
	justify-content: space-between;
}

.header-select-groupfolders-title {
	width: 90%;
}

.title-select-groupfolders {
	position: relative;
	left: 20px;
	font-weight: bold;
	font-size: 18px;
}

.select-groupfolders-list {
	flex-grow: 1;
	margin-top: 5px;
	border-style: solid;
	border-width: 1px;
	border-color: transparent;
	width: 82%;
	overflow: scroll;
}

.groupfolder-entry {
	justify-content: space-between;
	padding-left: 1px;
	font-size: 20px;
}

.groupfolder-entry,
.groupfolder-entry div {
	align-items: center;
	display: flex;
	flex-flow: row;
}

.groupfolder-name {
	margin-left: 10px;
	max-width: 440px;
}

.convert-space {
	cursor: pointer !important;
}

.select-groupfolders-actions {
	display: flex;
	flex-flow: row-reverse;
	margin-top: 10px;
}

</style>
