<template>
    <div>
        <div v-if="!scanning && !scanComplete">
            <h2 class="scf__content-header">{{translations.batch_process_images}}</h2>
            <div class="scf__content-text">{{translations.batch_process_description}}</div>
            <button class="scf__content-button"
                    v-on:click="scan"
            >
                {{translations.scan_for_images}}
            </button>
        </div>
        <div v-if="scanning">
            <h2 class="scf__content-header">{{translations.scanning_in_progress}}</h2>
            <div class="scf__progress-bar">
                <div class="scf__progress-bar-progress" v-bind:style="{width: this.progress + '%'}"></div>
            </div>
            <div class="scf__content-text">{{progressText}}</div>
        </div>
        <div v-if="scanComplete">
            <div class="csf__content-section">
                <h2 class="scf__content-header">{{translations.scan_complete}}</h2>
                <div class="scf__content-text">{{scanResultsText}}</div>
                <button class="scf__content-button"
                        v-on:click="scan"
                        v-if="this.postsToFix.length > 0"
                >
                    {{translations.fix_images}}
                </button>
            </div>
            <div class="csf__content-section">
                <h2 class="scf__content-header">{{translations.image_list}}</h2>
                <div class="scf__list">
                    <a class="scf__list-item"
                       v-for="post in postsToFix"
                       v-bind:href="post.link"
                       target="_blank"
                    >
                        <img v-if="post.thumbnail" v-bind:src="post.thumbnail" class="scf__list-item-thumbnail">
                        <div v-if="!post.thumbnail" class="scf__list-item-thumbnail"></div>
                        <div>
                            <div class="scf__list-item-title">{{post.title}}</div>
                            <div class="scf__list-item-subtitle">{{post.icc}}</div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import axios from "axios";
    import qs from "qs";
    import {sprintf} from 'sprintf-js';

    export default {
        data: function () {
            return {
                translations: window.csf_translations,
                ids: [],
                scanning: false,
                scanComplete: false,
                total: 0,
                processed: 0,
                currentIndex: 0,
                currentId: null,
                postsToFix: [],
            }
        },
        methods: {
            scan: function () {
                this.scanning = true;
                let data = {
                    'action': 'csf_scan_images',
                };

                axios.post(ajaxurl, qs.stringify(data))
                    .then(response => {
                        this.ids = response.data.posts;
                        this.total = response.data.total;

                        if (this.ids.length) {
                            this.currentId = this.ids[this.currentIndex];
                            this.getImage(this.currentId);
                        }
                    }).catch((error) => {
                        console.error(error)
                        this.$errorToast(this.translations.generic_error)
                        this.scanning = false;
                    })
            },
            getImage(id) {
                let data = {
                    'action': 'csf_get_image',
                    'csf_post_id': id,
                };

                axios.post(ajaxurl, qs.stringify(data)).then(response => {
                    if (response.data.success && response.data.fix) {
                        this.postsToFix.push(response.data.post)
                    }
                }).catch(response => {
                    // TODO: handle error?
                }).finally(() => {
                    this.processed = this.processed + 1;
                    let newIndex = this.currentIndex + 1;
                    if (this.ids[newIndex]) {
                        this.currentIndex = newIndex;
                        this.getImage(this.ids[newIndex]);
                    } else {
                        this.scanning = false;
                        this.scanComplete = true;
                    }
                });
            }
        },
        computed: {
            progress: function () {
                return this.processed / this.total * 100;
            },
            progressText: function () {
                if (this.total === 0) {
                    return sprintf(this.translations.scan_progress_short, this.total);
                } else if (this.processed === 1) {
                    return sprintf(this.translations.scan_progress, this.progress, this.processed, this.total);
                } else {
                    return sprintf(this.translations.scan_progress_plural, this.progress, this.processed, this.total);
                }
            },
            scanResultsText: function () {
                return this.postsToFix.length === 1 ?
                    sprintf(this.translations.scan_results, this.total, this.postsToFix.length) :
                    sprintf(this.translations.scan_results_plural, this.total, this.postsToFix.length);
            },
        }
    }
</script>