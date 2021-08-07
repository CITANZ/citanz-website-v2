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
