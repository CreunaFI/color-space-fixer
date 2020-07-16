<template>
    <div class="csf">
        <div class="csf__header">
            <img src="./header-icon.png" alt="" class="csf__header-icon" height="22" width="22">
            <div class="csf__header-title">{{translations.plugin_name}}</div>
            <button class="csf__header-button"
                    v-on:click="currentTab = 'settings'"
                    v-bind:class="{'csf__header-button--active': currentTab === 'settings'}"
            >{{translations.options}}</button>
            <button class="csf__header-button"
                    v-on:click="currentTab = 'batch'"
                    v-bind:class="{'csf__header-button--active': currentTab === 'batch'}"
            >{{translations.batch_process_images}}</button>
        </div>
        <div class="scf__content">
            <Options v-if="currentTab === 'settings'" />
            <Batch v-if="currentTab === 'batch'" />
        </div>
    </div>
</template>

<script>
    import Batch from "./Batch";
    import Options from "./Options";
    export default {
        components: {Options, Batch},
        data: function () {
            return {
                currentTab: 'settings',
                translations: window.csf_translations,
            }
        },
    }
</script>