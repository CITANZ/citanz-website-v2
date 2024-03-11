<template>
  <div class="page-content">
    <section-title />
    <page-hero v-if="site_data.hero" :hero="site_data.hero" />
    <v-container>
      <v-row>
        <v-col cols="12" sm="10" md="7" offset-sm="1">
          <v-breadcrumbs
            class="pl-0 mb-12"
            :items="breadcrumbItems"
            large
          ></v-breadcrumbs>
          <v-row>
            <v-col>
              <dl class="page-content__misc">
                <dt><strong>Company</strong></dt>
                <dd v-if="site_data?.company?.link?.url?.length"><a target="_blank" :href="site_data.company.link.url">{{ site_data.company.title }}</a></dd>
                <dd v-else>{{ site_data.company.title }}</dd>
                <dt><strong>Listed until</strong></dt>
                <dd>{{ listedUntil }}</dd>
                <dt><strong>Posted by</strong></dt>
                <dd>{{ site_data.postedBy }}</dd>
                <dt><strong>Can support RV application?</strong></dt>
                <dd>{{ site_data.wtr ? 'Yes' : 'Not specified' }}</dd>
              </dl>
            </v-col>
            <v-col cols="auto">
              <dl class="page-content__misc">
                <dt><strong>Viewed</strong></dt>
                <dd class="text-right pr-2">{{ site_data.viewCount + 1 }}</dd>
                <dt><strong>Applied</strong></dt>
                <dd class="text-right pr-2">{{ site_data.appliedCount }}</dd>
              </dl>
            </v-col>
          </v-row>
          <v-divider class="mt-8 mb-8"></v-divider>
          <article v-if="site_data.content?.length" class="typography" v-html="site_data.content"></article>
          <template v-if="site_data.faqs?.length">
            <h2 class="mt-16 mb-2">FAQ</h2>
            <v-divider class="mb-6"></v-divider>
            <div class="page-content__faq-holder" v-html="site_data.faqs"></div>
          </template>
        </v-col>
        <v-col cols="12" md="4" class="text-center" v-if="user.isPaidMember || user.neverExpire">
          <p id="btn-apply-job-holder" class="mt-12 mt-md-25"><v-btn :disabled="site_data.hasApplied" id="btn-apply-job" color="primary" x-large @click.prevent="showApplicationForm = !showApplicationForm">Apply</v-btn></p>
          <p class="text-body-2" v-if="site_data.hasApplied"><em><small>You have already applied for this job</small></em></p>
        </v-col>
        <v-col cols="12" md="4" class="text-center" v-else>
          <p id="btn-apply-job-holder" class="mt-12 mt-md-25"><v-btn :disabled="true" id="btn-apply-job" color="primary" x-large>Apply</v-btn></p>
          <p><small>Sorry, only the active member can apply.</small><br /><router-link to="/member/membership">Renew membership</router-link></p>
        </v-col>
      </v-row>
      <v-dialog
        v-model="showApplicationForm"
        max-width="600"
      >
        <job-application-form
          v-if="showApplicationForm"
          @close-form="showApplicationForm = false"
          @on-complete="onApplicationComplete"
        />
      </v-dialog>
    </v-container>
  </div>
</template>
<script>
import memberMixin from '../../../mixins/memberMixin'
import * as dayjs from 'dayjs'
import JobApplicationForm from '../../blocks/forms/JobApplicationForm'

export default {
  name: "ReferralPage",
  mixins: [ memberMixin ],
  components: { 'job-application-form': JobApplicationForm },
  data () {
    return {
      showApplicationForm: false,
    }
  },
  computed: {
    listedUntil () {
      return dayjs(this.site_data.until).format('DD/MM/YYYY')
    },
    breadcrumbItems () {
      return [
        {
          text: 'Home',
          disabled: false,
          href: '/',
        },
        {
          text: 'Referrals',
          disabled: false,
          href: '/referral-opportunities',
        },
        {
          text: this.site_data.title,
          disabled: true,
          href: location.pathname,
        },
      ]
    },
  },
  methods: {
    onApplicationComplete () {
      this.showApplicationForm = false
      this.site_data.hasApplied = true
      alert('Application successfully submitted')
    },
  },
  mounted () {
    this.$nextTick().then(() => {
      this
        .post({
          path: `/api/v/1/job-referral/viewJobPage/${this.site_data.id}`,
          headers: {
            headers: { Authorization: `Bearer ${this.access_token.access_token}` },
          },
        })
    })
  },
}
</script>
<style lang="scss" scoped>
.page-content {
  &__misc {
    dd + dt {
      margin-top: 1.25rem;
    }
  }
}

#btn-apply-job-holder {
  position: sticky;
  top: 6rem;
}
</style>