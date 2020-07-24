<template>
    <div v-bind:class="{'csf__options-loading': loading}">
        <h2 class="scf__content-header">{{translations.options}}</h2>
        <div class="scf__checkbox">
            <label class="scf-custom-checkbox">
                <input type="checkbox" v-model="options.process_on_upload">
                <span class="scf-custom-checkbox-indicator"></span>
                Process images on upload
            </label>
        </div>
        <div class="scf__checkbox">
            <label class="scf-custom-checkbox">
                <input type="checkbox" v-model="options.show_media_column">
                <span class="scf-custom-checkbox-indicator"></span>
                Show color space information on media list page
            </label>
        </div>

        <button class="scf__content-button"
                v-on:click="save"
        >
            {{translations.save}}
        </button>
    </div>
</template>
<script>
    import axios from "axios";
    import * as qs from "qs";

    export default {
        data: function () {
            return {
                translations: window.csf_translations,
                loading: true,
                options: {}
            }
        },
        mounted() {
            let data = {
                'action': 'csf_get_options',
            };
            axios.post(ajaxurl, qs.stringify(data)).then(response => {
                this.options = response.data;
                this.loading = false;
            }).catch(error => {
                console.error(error);
            })
        },
        methods: {
            save: function () {

                let data = {
                    'action': 'csf_save_options',
                    'csf_options': JSON.stringify(this.options),
                };

                axios.post(ajaxurl, qs.stringify(data))
                    .then(response => {
                        console.log(response);
                        this.$toasted.show(this.translations.options_saved);
                    }).catch((error) => {
                    console.error(error)
                })


            },
        }
    }
</script>