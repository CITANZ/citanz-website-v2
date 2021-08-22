import Vue from 'vue'
import Router from 'vue-router'
import Base from '../components/pages/Base'
import MemberCentre from '../components/pages/types/MemberCentre'

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
      return { x: 0, y: 0 };
    }
})
