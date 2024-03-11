<template>
  <div class="payments-section">
    <v-row>
      <v-col>
        <h2 class="mb-4">Referral Opportunities</h2>
      </v-col>
      <v-col v-if="canListJobs" cols="auto">
        <v-btn
          color="primary"
          @click.prevent="dialog = !dialog"
        >
          <v-icon left>
            mdi-plus
          </v-icon>
          Create
        </v-btn>
      </v-col>
    </v-row>
    <v-progress-linear
      v-if="!sectionDataLoaded"
      indeterminate
      rounded
      height="6"
    ></v-progress-linear>
    <div class="not-eligible-message" v-else-if="!canListJobs">
      <p>You are not eligible to use this feature. Please talk to CITANZ's officers to get approval</p>
    </div>
    <template v-else>
      <v-data-table
        v-if="sectionData.list?.length"
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
            @click="editListing(item)"
          >
            mdi-open-in-new
          </v-icon>
          <v-icon
            small
            @click="editListing(item)"
          >
            mdi-file-edit-outline
          </v-icon>
        </template>
      </v-data-table>
      <v-pagination
        v-if="sectionData && canListJobs && sectionData.list?.length"
        v-model="page"
        class="my-4"
        :length="sectionData.pages"
        :total-visible="5"
      ></v-pagination>
      <p>You haven't uploaded any referral Opportunities. Click the <span class="v-btn v-btn--has-bg theme--light v-size--x-small primary"><span class="v-btn__content"><i aria-hidden="true" class="v-icon notranslate v-icon--left mdi mdi-plus theme--light mx-0"></i></span>Create</span> button to add one</p>
    </template>
    <v-dialog
      v-model="dialog"
      max-width="600"
    >
      <job-creation-form
        v-if="dialog"
        :listingID="listing"
        @close-form="closeForm"
        @on-complete="onComplete"
      />
    </v-dialog>
  </div>
</template>
<script>
  import * as dayjs from 'dayjs'
  import memberMixin from '../../../../mixins/memberMixin'
  import JobCreationForm from '../../../blocks/forms/JobCreationForm'
  import { mapActions } from 'vuex'
  
  export default {
    name: 'jobs-section',
    mixins: [ memberMixin ],
    components: {
      'job-creation-form': JobCreationForm,
    },
    data() {
      return {
        page: this.$route.query.page ? parseInt(this.$route.query.page) : 1,
        listing: this.$route.query.listing ? this.$route.query.listing : null,
        dialog: false,
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
      },
      dialog(nv) {
        if (!nv) {
          this.setSkipFetchOnce(true)
          this.listing = null
          if (this.$route.listing?.length && this.$route.page?.length) {
            this.$router.push({ query: { page: this.page } })
          } else if (location.search?.length) {
            this.$router.push({ query: null })
          }
        }
      },
    },
    created() {
      this.loadSectionData()
    },
    computed: {
      headers() {
        return [
          {
            text: 'List until',
            align: 'start',
            value: 'until',
          },
          { text: 'Company', value: 'company.title', sortable: false },
          { text: 'Job title', value: 'rawTitle', sortable: false },
          { text: 'Viewed', align: 'center', value: 'viewCount', sortable: false },
          { text: 'Applicants', align: 'center', value: 'appliedCount', sortable: false },
          { text: 'Actions', align: 'end', value: 'actions', sortable: false },
        ]
      },
      canListJobs () {
        return this.user?.canListJob
      }
    },
    methods: {
      ...mapActions(['get', 'setSkipFetchOnce']),
      loadSectionData () {
        if (!this.user) {
          this.$router.replace('/member')
          return
        }
  
        if (this.refreshingToken || !this.accessToken) {
          return
        }

        this.sectionDataLoaded = false
  
        this.get(
          {
            path: `/api/v/1/member/getJobs?page=${this.page}`,
            headers: {
              headers: { Authorization: `Bearer ${this.accessToken.access_token}` },
            },
          }
        ).then(resp => {
          this.sectionDataLoaded = true
          this.sectionData = resp.data
          this
            .sectionData
            .list
            .forEach(x => {
              x.until = dayjs(x.until).format('DD/MM/YYYY')
            });
        })
      },
      editListing(listing) {
        if (this.listing != listing.id) {
          this.setSkipFetchOnce(true)
          this.listing = listing.id
          this.$router.push({query: {page: this.page, listing: listing.id}})
        }
  
        this.dialog = true
      },
      closeForm () {
        this.dialog = false
      },
      onComplete () {
        this.closeForm()
        this.loadSectionData()
      }
    },
  }
</script>
