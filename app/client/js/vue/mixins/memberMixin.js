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
    ...mapActions(['get', 'post']),
  }
}
