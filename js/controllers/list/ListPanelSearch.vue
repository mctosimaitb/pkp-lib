<template>
	<div class="pkpListPanel__search">
		<label>
			<span class="pkp_screen_reader">{{ i18n.search }}</span>
			<input type="search"
				@keyup="searchPhraseChanged"
				:id="inputId"
				:value="searchPhrase"
				:placeholder="i18n.search"
			>
			<span class="pkpListPanel__searchIcons">
				<span class="pkpListPanel__searchIcons--search"></span>
			</span>
		</label>
		<button class="pkpListPanel__searchClear"
			v-if="searchPhrase"
			@click.prevent="clearSearchPhrase"
			:aria-controls="inputId"
		>
			<span class="fa fa-times"></span>
			<span class="pkp_screen_reader">{{ i18n.clearSearch }}</span>
		</button>
	</div>
</template>

<script>
export default {
	name: 'ListPanelSearch',
	props: ['searchPhrase', 'i18n'],
	computed: {
		inputId: function() {
			return 'list-panel-search-' + this._uid;
		},
	},
	methods: {
		/**
		 * A throttled function to signal to the parent element that the
		 * searchPhrase should be updated. It's throttled to allow it to be
		 * fired by frequent events, like keyup.
		 *
		 * @param string|object data A DOM event (object) or the new search
		 *  phrase (string)
		 */
		searchPhraseChanged: _.debounce(function(data) {
			var newVal = typeof data === 'string' ? data : data.target.value;
			this.$emit('searchPhraseChanged', newVal);
		}, 250),

		/**
		 * Clear the search phrase
		 */
		clearSearchPhrase: function() {
			this.$emit('searchPhraseChanged', '');
			this.$nextTick(function() {
				this.$el.querySelector('input[type="search"]').focus();
			});
		},
	},
}
</script>
