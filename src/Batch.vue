<template>
    <div>
        <h2 class="scf__content-header">{{translations.batch_process_images}}</h2>
        <div class="scf__content-text">{{translations.batch_process_description}}</div>
        <button class="scf__content-button">{{translations.scan_for_images}}</button>
    </div>
</template>

<script>
    export default {
        data: function () {
            return {
                translations: window.csf_translations,
            }
        },
    }
</script>