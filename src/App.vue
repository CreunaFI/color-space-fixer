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
                <div class="scf__checkbox">
                    <label class="scf-custom-checkbox">
                        <input type="checkbox">
                        <span class="scf-custom-checkbox-indicator"></span>
                        Process images on upload
                    </label>

                </div>

                <button class="scf__content-button"
                        v-on:click="save"
                >
                    {{translations.save}}
                </button>
            </div>
            <Batch v-if="currentTab === 'batch'">
            </Batch>
        </div>
    </div>
</template>

<script>
    import Batch from "./Batch";
    export default {
        components: {Batch},
        data: function () {
            return {
                currentTab: 'settings',
                translations: window.csf_translations,
            }
        },
        methods: {
            save: function () {
                this.$toasted.show(this.translations.options_saved);
            }
        }
    }
</script>