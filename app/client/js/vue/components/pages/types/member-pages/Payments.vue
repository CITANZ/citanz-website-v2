<template>
  <div class="payments-section">
    <h2 class="mb-4">Payment History</h2>
    <v-progress-linear
      v-if="!sectionDataLoaded"
      indeterminate
      rounded
      height="6"
    ></v-progress-linear>
    <v-data-table
      v-else
      :headers="headers"
      :items="sectionData.list"
      sort-by="date"
      :items-per-page="sectionData.pageSize"
      :sort-desc="true"
      :hide-default-footer="true"
    >
      <template v-slot:item.actions="{ item }">
        <v-icon
          class="mr-3"
          small
          @click="viewOrder(item)"
        >
          mdi-magnify
        </v-icon>
      </template>
    </v-data-table>
    <v-pagination
      v-model="page"
      class="my-4"
      :length="sectionData.pages"
      :total-visible="5"
    ></v-pagination>
  </div>
</template>

<script>
import memberMixin from '../../../../mixins/memberMixin'

export default {
  name: 'payments-section',
  mixins: [ memberMixin ],
  data() {
    return {
      page: this.$route.query.page ? parseInt(this.$route.query.page) : 1,
    }
  },
  watch: {
    page(nv) {
      this.$router.push({query: {page: nv}})
    },
    refreshingToken(nv, ov) {
      if (!ov && nv) {
        this.loadSectionData()
      }
    }
  },
  created() {
    this.loadSectionData()
  },
  computed: {
    headers() {
      return [
        {
          text: 'Order#',
          align: 'start',
          sortable: false,
          value: 'ref',
        },
        { text: 'Date', value: 'date' },
        { text: 'Amount', value: 'amount' },
        { text: 'Status', align: 'center', value: 'status', sortable: false },
        { text: 'Actions', align: 'end', value: 'actions', sortable: false },
      ]
    },
  },
  methods: {
    loadSectionData() {
      if (this.refreshingToken || !this.accessToken) {
        return
      }

      this.get(
        {
          path: `/api/v/1/member/getPayments?page=${this.page}`,
          headers: {
            headers: { Authorization: `Bearer ${this.accessToken.access_token}` },
          },
        }
      ).then(resp => {
        this.sectionDataLoaded = true
        this.sectionData = resp.data
      })
    },
    viewOrder(order) {
      console.log(order)
    },
  },
}
</script>
