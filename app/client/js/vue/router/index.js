import Vue from 'vue'
import Router from 'vue-router'
import Base from '../components/pages/Base'

Vue.use(Router)

export default new Router({
    mode: 'history',
    routes: [
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
