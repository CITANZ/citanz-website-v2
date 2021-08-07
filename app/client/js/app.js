// Polyfills
import "core-js/stable"
import "regenerator-runtime/runtime"

import Vue from "vue"
import vuetify from "./plugins/vuetify" // path to vuetify export
import App from "./vue/App"
import store from './vue/store'
import router from "./vue/router"
import common from "./vue/mixins/common"

Vue.mixin(common)

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
