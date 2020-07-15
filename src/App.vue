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
            <div v-if="currentTab === 'settings'">
                <h2 class="scf__content-header">{{translations.options}}</h2>
                <div class="scf__content-text">{{translations.batch_process_description}}</div>

                <button class="scf__content-button">{{translations.save}}</button>
            </div>
            <div v-if="currentTab === 'batch'">
                <h2 class="scf__content-header">{{translations.batch_process_images}}</h2>

                <div class="scf__content-text">{{translations.batch_process_description}}</div>

                <button class="scf__content-button">{{translations.scan_for_images}}</button>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data: function () {
            return {
                currentTab: 'settings',
                translations: window.csf_translations,
            }
        }
    }
</script>