import 'focus-visible';
import Vue from 'vue';
import App from "./App";

document.addEventListener('DOMContentLoaded', () => {
    let element = document.getElementById('color-space-fixer');
    if (element) {
        new Vue({
            el: element,
            components: {
                'color-space-fixer': App,
            },
        });
    }
});