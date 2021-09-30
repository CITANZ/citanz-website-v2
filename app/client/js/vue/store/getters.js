export default {
  isLoading(state) {
    return state.isLoading
  },
  isMobile(state) {
    return state.isMobile
  },
  error(state) {
    return state.error
  },
  site_data(state) {
    return state.site_data
  },
  showSigninForm(state) {
    return state.showSigninForm
  },
  access_token(state) {
    return state.access_token
  },
  showModal(state) {
    return state.showModal
  },
  postbackMessage(state) {
    return state.postbackMessage
  },
  modalColor(state) {
    return state.modalColor
  },
  user(state) {
    return state.user
  },
  stripeKey(state) {
    return state.stripeKey
  },
  refreshingToken(state) {
    return state.refreshingToken
  },
  skipFetchOnce(state) {
    return state.skipFetchOnce
  },
  width(state) {
    return state.width
  },
  memberMenuShown(state) {
    return state.memberMenuShown
  },
}
