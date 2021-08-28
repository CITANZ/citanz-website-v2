// Polyfills
import "core-js/stable"
import "regenerator-runtime/runtime"

import Vue from "vue"
import vuetify from "./plugins/vuetify" // path to vuetify export
import App from "./vue/App"
import store from './vue/store'
import router from "./vue/router"
import common from "./vue/mixins/common"
import TitleSection from './vue/components/blocks/TitleSection'
import PageHero from './vue/components/blocks/PageHero'
import VuetifyGoogleAutocomplete from 'veoci-vuetify-google-autocomplete'

Vue.mixin(common)
Vue.component('section-title', TitleSection)
Vue.component('page-hero', PageHero)

Vue.use(VuetifyGoogleAutocomplete, {
  apiKey: 'AIzaSyB-8GlwgujFA8-rDO0TWu0grdi8wFnCoQA',
})

new Vue({
  el: "#app",
  vuetify,
  store,
  router,
  components: {
    App
  },
  template: "<App/>"
})
