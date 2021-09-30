import { mapGetters, mapActions } from 'vuex'
export default {
  data() {
    return {
      sectionDataLoaded: false,
      sectionData: null,
    }
  },
  emits: ['data-loaded'],
  computed: {
    ...mapGetters(['refreshingToken','access_token', 'user']),
    accessToken() {
      return this.access_token
    },
  },
  watch: {
    user() {
      if (!this.user) {
        this.sectionDataLoaded = false
        this.sectionData = null
        if (this.$route.path != '/member/me') {
          this.$router.replace('/member')
        }
      }
    }
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
