import 'focus-visible';
import Vue from 'vue';
import App from "./App";
import Toasted from 'vue-toasted';

document.addEventListener('DOMContentLoaded', () => {

    // you can also pass options, check options reference below
    Vue.use(Toasted, {
        position: 'bottom-right',
        duration: 2000,
    })

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