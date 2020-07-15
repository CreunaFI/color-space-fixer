<template>
    <div class="csf">
        <div class="csf__header">
            <img src="./header-icon.png" alt="" class="csf__header-icon" height="22" width="22">
            <div class="csf__header-title">Color Space Fixer</div>
            <button class="csf__header-button"
                    v-on:click="currentTab = 'settings'"
                    v-bind:class="{'csf__header-button--active': currentTab === 'settings'}"
            >Settings</button>
            <button class="csf__header-button"
                    v-on:click="currentTab = 'batch'"
                    v-bind:class="{'csf__header-button--active': currentTab === 'batch'}"
            >Batch process images</button>
        </div>
        <div class="scf__content">
            <div v-if="currentTab === 'settings'">
                <h2 class="scf__content-header">Options</h2>
            </div>
            <div v-if="currentTab === 'batch'">
                <h2 class="scf__content-header">Batch process images</h2>

                <div class="scf__content-text">This tool will scan your WordPress media library for images not in sRGB color space.</div>

                <button class="scf__content-button">Scan for images</button>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data: function () {
            return {
                currentTab: 'settings',
            }
        }
    }
</script>