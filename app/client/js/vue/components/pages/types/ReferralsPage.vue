<template>
  <div class="page-content">
    <section-title />
    <page-hero v-if="site_data.hero" :hero="site_data.hero" />
    <v-container class="pt-10">
      <v-row>
        <v-col cols="12" sm="10" md="7" offset-sm="1">
          <article v-if="site_data.content?.length" class="typography" v-html="site_data.content"></article>
        </v-col>
      </v-row>
    </v-container>
    <v-container class="pt-10" v-if="site_data.list?.length">
      <v-row>
        <v-col cols="12" sm="10" offset-sm="1">
          <v-data-table
            class="referral-table"
            :headers="headers"
            :items="site_data.list"
            sort-by="date"
            :items-per-page="site_data.pageSize"
            :sort-desc="true"
            :hide-default-footer="true"
          >
            <template v-slot:item.wtr="{ item }">
              <v-chip
                :color="item.wtr ? 'green' : 'grey'"
                dark
              >
                {{ item.wtr ? '✔' : '-' }}
              </v-chip>
            </template>
            <template v-slot:item.rawTitle="{ item }">
              <div class="referral__job-title">{{ item.rawTitle }}</div>
            </template>
            <template v-slot:item.company="{ item }">
              <span class="text-nowrap" v-html="item.company"></span>
            </template>
            <template v-slot:item.workLocation="{ item }">
              <div v-html="item.workLocation"></div>
            </template>
            <template v-slot:item.postedBy="{ item }">
              <span class="text-nowrap" v-html="item.postedBy"></span>
            </template>
            <template v-slot:item.actions="{ item }">
              <v-btn :to="`/referral-opportunities/view/${item.id}`" color="primary" depressed>Apply</v-btn>
            </template>
          </v-data-table>
          <v-pagination
            v-if="site_data.list"
            v-model="page"
            class="my-4"
            :length="site_data.pages"
            :total-visible="5"
          ></v-pagination>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>
<script>
import memberMixin from '../../../mixins/memberMixin'

export default {
  name: "ReferralsPage",
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
  },
  computed: {
    headers() {
      return [
      { text: 'WTR?', value: 'wtr', sortable: false },
        { text: 'Job title', value: 'rawTitle', sortable: false },
        { text: 'Company', value: 'company.title', sortable: false },
        { text: 'Location', align: 'center', value: 'workLocation', sortable: false },
        {
          text: 'Close on',
          align: 'center',
          value: 'until',
        },
        { text: 'Listed by', align: 'end', value: 'postedBy', sortable: false },
        { text: '', align: 'end', value: 'actions', sortable: false },
      ]
    },
  },

}
</script>
<style lang="scss">
.v-data-table.referral-table > .v-data-table__wrapper > table > tbody > tr {
  > td {
    font-size: 1rem;
    .v-chip {
      width: 32px;
      height: 32px;
      padding-right: 0;
      padding-left: 0;
      justify-content: center;
    }
  }
}

span.text-nowrap {
  white-space: nowrap;
}
</style>