export default {
  SET_ISLOADING(state, status) {
    state.isLoading = status
  },
  SET_IS_MOBILE(state, isMobile) {
    state.isMobile = isMobile
  },
  SET_ERROR(state, error) {
    state.error = error
  },
  SET_SITE_DATA(state, site_data) {
    state.site_data = site_data
    if (site_data) {
      document.title = site_data.title
    }
  },
  TOGGLE_SIGNIN_FORM(state, status) {
    if (status !== undefined) {
      state.showSigninForm = status
    } else {
      state.showSigninForm = !state.showSigninForm
    }
  },
  SET_ACCESS_TOKEN(state, token) {
    state.access_token = token
  },
  SET_USER(state, user) {
    state.user = user
  },
  SET_SHOW_MODAL(state, status) {
    state.showModal = status
  },
  SET_POSTBACK_MESSAGE(state, message) {
    state.postbackMessage = message
  },
  SET_MODAL_COLOR(state, color) {
    state.modalColor = color
  },
  SET_STRIPE_KEY(state, key) {
    state.stripeKey = key
  },
  SET_REFRESHING_TOKEN(state, status) {
    state.refreshingToken = status
  },
  SET_SKIP_FETCH(state, status) {
    state.skipFetchOnce = status
  },
}
