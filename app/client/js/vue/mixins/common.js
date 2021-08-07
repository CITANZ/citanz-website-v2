export default {
  computed: {
    site_data() {
      return this.$store.state.site_data
    },
    site_nav() {
      return this.site_data.navigation
    }
  },
}
