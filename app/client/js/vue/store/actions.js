import axios from 'axios'
import { v4 as uuidv4 } from 'uuid'
import S3 from 'aws-sdk/clients/s3'

axios.defaults.headers.common = {
  'X-Requested-With': 'XMLHttpRequest',
}

// const s3 = new S3({
//   accessKeyId: process.env.AWS_ACCESS_KEY_ID,
//   secretAccessKey: process.env.AWS_SECRET_ACCESS_KEY,
//   region: 'ap-southeast-2',
// })

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

  put({ commit }, payload) {
    return new Promise((resolve, reject) => {
      axios.put(payload.path, payload.data, payload.headers).then(resolve).catch(reject)
    })
  },

  delete({ commit }, payload) {
    return new Promise((resolve, reject) => {
      axios.delete(payload.path, payload.headers).then(resolve).catch(reject)
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

  setMemberMenuShown({ commit }, status) {
    commit('SET_MEMBER_MENU_STATE', status)
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

  getPageData({ commit, state }, path) {
    commit('SET_ERROR', null)
    commit('SET_SITE_DATA', null)

    return new Promise((resolve, reject) => {
      const payload = state.access_token && state.access_token.access_token ? {
        headers: { Authorization: `Bearer ${state.access_token.access_token}` },
      } : null
      
      axios.get(path, payload).then(resp => {
        if (location.pathname == '/referral-opportunities' || location.pathname.startsWith('/referral-opportunities/')) {
          const referralItem = resp.data.navigation.find(x => x.url == '/referral-opportunities')
          referralItem.active = true
        }

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
  },
  setScrolled({ commit }, status) {
    commit('SET_SCROLLED', status)
  },
  uploadToBucket ({ state }, payload) {
    return new Promise((resolve, reject) => {
      const jwtToken = state.access_token && state.access_token.access_token ? {
        headers: { Authorization: `Bearer ${state.access_token.access_token}` },
      } : null

      axios
        .get('/api/v/1/session/getAAK', jwtToken)
        .then(resp => {
          const { key, secret, region } = resp.data
          const s3 = new S3({
            accessKeyId: key,
            secretAccessKey: secret,
            region: region,
          })

          const uuid = payload.id
          const file = payload.file
          const fileExtension = file.name.split('.')[1]
          const newName = `${uuidv4()}`
          const params = {
            Bucket: 'citanz',
            Key: `${uuid}/${newName}.${fileExtension}`,
            Body: file,
            Metadata: {
              'original-filename': file.name,
              'owner-uuid': uuid,
            },
          }
        
          s3.upload(params, (err, data) => {
            if (err) {
              reject(err)
            } else {
              resolve(data)
            }
          })
        })
    })
  },
  deleteFromBucket ({ state }, s3bucketkey) {
    return new Promise((resolve, reject) => {
      const jwtToken = state.access_token && state.access_token.access_token ? {
        headers: { Authorization: `Bearer ${state.access_token.access_token}` },
      } : null
      
      axios
        .get('/api/v/1/session/getAAK', jwtToken)
        .then(resp => {
          const { key, secret, region } = resp.data
          const s3 = new S3({
            accessKeyId: key,
            secretAccessKey: secret,
            region: region,
          })

        const params = {
          Bucket: 'citanz',
          Key: s3bucketkey,
        }
      
        s3.deleteObject(params, (err, data) => {
          if (err) {
            reject(err)
          } else {
            resolve(data)
          }
        })
      })
    })
  },
  getBucketObjectHeader ({ state }, s3bucketkey) {
    return new Promise((resolve, reject) => {
      const jwtToken = state.access_token && state.access_token.access_token ? {
        headers: { Authorization: `Bearer ${state.access_token.access_token}` },
      } : null

      axios
        .get('/api/v/1/session/getAAK', jwtToken)
        .then(resp => {
          const { key, secret, region } = resp.data
          const s3 = new S3({
            accessKeyId: key,
            secretAccessKey: secret,
            region: region,
          })

        const params = {
          Bucket: 'citanz',
          Key: s3bucketkey,
        }
      
        s3.headObject(params, (err, data) => {
          if (err) {
            reject(err)
          } else {
            resolve(data)
          }
        })
      })
    })
  },
}
