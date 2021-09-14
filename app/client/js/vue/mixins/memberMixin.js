import { mapGetters, mapActions } from 'vuex'
export default {
  data() {
    return {
      sectionDataLoaded: false,
      sectionData: false,
    }
  },
  emits: ['data-loaded'],
  computed: {
    ...mapGetters(['refreshingToken','access_token']),
    accessToken() {
      return this.access_token
    },
  },
  methods: {
    ...mapActions(['get', 'post', 'setUser']),
    syncMemberData(cbf) {
      this.get(
          '/api/v/1/member',
          { headers: { Authorization: `Bearer ${this.accessToken.access_token}` }
        })
        .then(resp => {
          this.setUser(resp.data)
          this.$nextTick().then(cbf)
        }).catch(console.error)
    }
  }
}
