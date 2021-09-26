import axios from "axios"
axios.defaults.headers.common = {
  'X-Requested-With': 'XMLHttpRequest'
}
export default {
  setLoading({ commit }, status) {
    commit("SET_ISLOADING", status)
  },

  setMobile({ commit }, status) {
    commit("SET_IS_MOBILE", status)
  },

  setError({ commit }, error) {
    commit("SET_ERROR", error)
  },

  setSiteData({ commit }, data) {
    commit("SET_SITE_DATA", data)
  },

  get({ commit }, payload) {
    return new Promise((resolve, reject) => {
      axios.get(payload.path, payload.headers).then(resolve).catch(reject)
    })
  },

  post({ commit }, payload) {
    return new Promise((resolve, reject) => {
      axios.post(payload.path, payload.data, payload.headers).then(resolve).catch(reject)
    })
  },

  doRecovery({ commit }, payload) {
    return new Promise((resolve, reject) => {
      axios.post(payload.path, payload.data, payload.headers).then(resolve).catch(reject)
    })
  },

  doSignup({ commit }, payload) {
    return new Promise((resolve, reject) => {
      axios.post(payload.path, payload.data, payload.headers).then(resolve).catch(reject)
    })
  },

  setStripeKey({ commit }, key) {
    commit('SET_STRIPE_KEY', key)
  },

  setRefreshingToken({ commit }, status) {
    commit('SET_REFRESHING_TOKEN', status)
  },

  refreshToken({ commit, dispatch }, payload) {
    return new Promise((resolve, reject) => {
      axios.post(
        'api/v/1/authorise',
        payload
      ).then(resolve).catch(reject)
    })
  },

  toggleSigninForm({ commit }, status) {
    commit('TOGGLE_SIGNIN_FORM', status)
  },

  setShowModal({ commit }, status) {
    commit('SET_SHOW_MODAL', status)
  },

  setPostbackMessage({ commit }, message) {
    commit('SET_POSTBACK_MESSAGE', message)
  },

  setModalColor({ commit }, color) {
    commit('SET_MODAL_COLOR', color)
  },

  setAccessToken({ commit }, token) {
    commit("SET_ACCESS_TOKEN", token)
  },

  setUser({ commit }, user) {
    commit('SET_USER', user)
  },

  setSkipFetchOnce({ commit }, status) {
    commit('SET_SKIP_FETCH', status)
  },

  setWidth({ commit }, width) {
    commit('SET_WIDTH', width)
  },

  getCSRFToken({ commit }, path) {
    return new Promise((resolve, reject) => {
      axios.get(path).then(resolve).catch(reject)
    })
  },

  getPageData({ commit }, path) {
    commit('SET_ERROR', null)
    commit('SET_SITE_DATA', null)

    return new Promise((resolve, reject) => {
      axios.get(path).then(resp => {
        commit('SET_SITE_DATA', resp.data)
        resolve(resp.data)
      }).catch(error => {
        commit('SET_ERROR', error)
        let code = 404

        if (error.response && error.response.status && error.response.data) {
            code = error.response.status
        }

        this.dispatch("getErrorPage", code)
        reject(code)
      })
    })
  },
  getErrorPage({ commit }, error_code) {
    axios.get(error_code).catch((error) => {
      if (error.response && error.response.data) {
        commit('SET_SITE_DATA', error.response.data)
      }
    })
  }
}
