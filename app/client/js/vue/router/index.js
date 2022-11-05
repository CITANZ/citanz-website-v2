import Vue from 'vue'
import Router from 'vue-router'
import Base from '../components/pages/Base'
import MemberCentre from '../components/pages/types/MemberCentre'
import goTo from 'vuetify/lib/services/goto'

Vue.use(Router)

export default new Router({
    mode: 'history',
    routes: [
      {
        path: '/member',
        redirect: '/member/me',
      },
      {
        path: '/member/:action',
        name: 'MemberCentre',
        component: MemberCentre,
      },
      {
        path: '*',
        name: 'Base',
        component: Base
      }
    ],
    scrollBehavior (to, from, savedPosition) {
      let scrollTo = 0

      if (to.hash) {
        scrollTo = to.hash
      } else if (savedPosition) {
        scrollTo = savedPosition.y
      }

      return goTo(scrollTo)
    }
})
