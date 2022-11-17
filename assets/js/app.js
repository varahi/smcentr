/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

import '../css/bootstrap.min.css';
import '../css/style.css';
import '../css/owl.theme.default.min.css';
import '../css/custom.css';
import '../css/media_queries.css';

//const $ = require('jquery');
//import jquery from '../js/jquery.min.js'
//import script from '../js/scripts.js'
//import bootstrap from '../js/bootstrap.bundle.min.js'
//import maskedinput from '../js/jquery.maskedinput.min.js'
//import simpleLoadMore from '../js/jquery.simpleLoadMore.js'

if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register("/sw.js", { scope: "/" }).then(() => {
        });
    });
}

/*
if('serviceWorker' in navigator){
    navigator.serviceWorker.register('/sw.js')
        .then(reg => console.log('service worker registered'))
        .catch(err => console.log('service worker not registered', err));
}*/
