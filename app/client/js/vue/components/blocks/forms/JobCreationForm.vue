<template>
<v-form
  :class="['form-job-creation px-8 pb-8', { 'pt-8': !tinyMCEReady }]"
  method="post"
  @submit.prevent="onSubmit"
  :disabled="busy"
  v-show="!linkModalOn"
>
  <v-progress-linear
    v-show="!tinyMCEReady || busy"
    indeterminate
    rounded
    height="6"
    class="inprogress-bar"
  ></v-progress-linear> 
  <div v-if="!busy" :class="['form-job-creation__inner', { 'visible': tinyMCEReady && !busy }]">
    <h2 class="form-title form-job-creation__inner__header">{{listingID ? 'Update' : 'Create'}} your opportunity...</h2>
    <v-text-field
      v-model="jobTitle"
      label="Job Title"
      required
    ></v-text-field>
    <v-autocomplete
      class="mb-4"
      v-model="company"
      :items="items"
      :loading="isLoading"
      :search-input.sync="search"
      chips
      clearable
      hide-details
      hide-selected
      item-text="title"
      item-value="id"
      label="Company"
      no-data-text="searching..."
      required
      flat
    >
      <template v-slot:no-data>
        <v-list-item v-if="!search?.length">
          <v-list-item-title>
            Search for the company that is hiring
          </v-list-item-title>
        </v-list-item>
        <v-list-item v-else-if="!company">
          <v-list-item-title v-if="!isLoading">
            Cannot find this comany, do you wish to <v-btn x-small color="primary" @click.prevent="setNewCompany">create</v-btn> it?
          </v-list-item-title>
          <v-list-item-title v-else>
            searching...
          </v-list-item-title>
        </v-list-item>
      </template>
      <template v-slot:selection="{ attr, on, item, selected }">
        <v-chip
          v-bind="attr"
          :input-value="selected"
          color="blue-grey"
          class="white--text"
          v-on="on"
        >
          <v-icon left>
            mdi-domain
          </v-icon>
          <span v-text="item.title"></span>
        </v-chip>
      </template>
      <template v-slot:item="{item}">
        <v-list-item-avatar
          color="indigo"
          class="text-h5 font-weight-light white--text"
          @click.prevent
        >
          {{ item.title.charAt(0) }}
        </v-list-item-avatar>
        <v-list-item-content>
          <v-list-item-title v-text="item.title"></v-list-item-title>
          <v-list-item-subtitle v-text="item.website"></v-list-item-subtitle>
        </v-list-item-content>
        <v-list-item-action>
          <v-icon>mdi-domain</v-icon>
        </v-list-item-action>
      </template>
    </v-autocomplete>
    <div class="tiny-mce-holder mb-4">
      <p class="mb-2">Job Description</p>
      <tinymce
        api-key="t68g356cw0kvcgqcxu7wrbien5qmx3k6hunhs62qtm2bklst"
        v-model="jobDescription"
        :init="{
          height: initialTinyHeight,
          menubar: false,
          plugins: [
            'advlist autolink lists link charmap anchor',
            'insertdatetime paste'
          ],
          toolbar:
            'formatselect | bold italic underline | \
            link bullist numlist outdent indent | removeformat'
          ,
          block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6',
          default_link_target: '_blank',
          setup: tinyMCEEventHandler
        }"
      />
    </div>
    <p v-if="!showFaqsField" class="mb-4"><v-btn color="primary" @click.prevent="showFaqsField = true" text>Have FAQs to add?</v-btn></p>
    <v-textarea
      v-else
      v-model="faqs"
      label="FAQs"
      hint="You can display some frequently asked questions and answers here"
      outlined
    ></v-textarea>
    <v-menu
      v-model="showCalendar"
      :close-on-content-click="false"
      :nudge-right="40"
      transition="scale-transition"
      offset-y
      min-width="auto"
    >
      <template v-slot:activator="{ on, attrs }">
        <v-text-field
          v-model="validUntil"
          label="When will this opportunity close?"
          prepend-icon="mdi-calendar"
          readonly
          v-bind="attrs"
          v-on="on"
        ></v-text-field>
      </template>
      <v-date-picker
        v-model="validUntil"
        @input="showCalendar = false"
      ></v-date-picker>
    </v-menu>
    <v-row class="form-job-creation__inner__footer pt-4" no-gutters v-if="!listingID">
      <v-col><p class="caption mb-0 grey--text font-italic">By pushing the <strong class="primary--text">SUBMIT</strong> button, you also agree to our Referral Opportunity policy.</p></v-col>
      <v-col cols="auto">
        <v-btn @click.prevent="$emit('close-form')">Cancel</v-btn>
      </v-col>
      <v-col cols="auto">
        <p class="ml-4"><v-btn type="submit" color="primary">Submit</v-btn></p>
      </v-col>
    </v-row>
    <v-row class="form-job-creation__inner__footer mt-4" v-else>
      <v-col></v-col>
      <v-col cols="auto">
        <v-btn @click.prevent="$emit('close-form')">Cancel</v-btn>
      </v-col>
      <v-col cols="auto">
        <v-btn color="error" @click.prevent="doDelete">Delete</v-btn>
      </v-col>
      <v-col cols="auto">
        <v-btn type="submit" color="primary">Update</v-btn>
      </v-col>
    </v-row>
  </div>
</v-form>
</template>
<script>
import memberMixin from '../../../mixins/memberMixin'
import Editor from '@tinymce/tinymce-vue'

export default {
  name: 'job-creation-form',
  mixins: [ memberMixin ],
  components: {
    'tinymce': Editor
  },
  emits: ['on-complete', 'close-form'],
  props: ['listingID'],
  data () {
    return {
      busy: false,
      showFaqsField: false,
      jobTitle: null,
      jobDescription: null,
      faqs: null,
      validUntil: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().substring(0, 10),
      company: null,
      search: null,
      items: [],
      isLoading: false,
      tinyMCEReady: false,
      showCalendar: false,
      linkModalOn: false,
      companyTimer: null,
    }
  },
  computed: {
    fields () {
      if (!this.model) return []

      return Object.keys(this.model).map(key => {
        return {
          key,
          value: this.model[key] || 'n/a',
        }
      })
    },
    initialTinyHeight () {
      return window.innerHeight / 3
    },
    typeInterval () { return 300 } , //ms
  },
  watch: {
    search (val, old) {
      this.company = null

      if (this.companyTimer) {
        clearTimeout(this.companyTimer)
        this.companyTimer = null
      }

      if (!val?.length) {
        this.isLoading = false
        return
      }

      this.isLoading = true

      if (val != old) {
        this.companyTimer = setTimeout(() => {
          this.get(
            {
              path: `/api/v/1/company?q=${val}`,
              headers: {
                headers: { Authorization: `Bearer ${this.accessToken.access_token}` },
              },
            }
          ).then(resp => {
            this.items = resp.data
          }).catch(error => {
            console.log(error)
            if (error.response && error.response.data && error.response.data.message) {
              alert(error.response.data.message)
            }
          }).finally(() => {
            this.isLoading = false
            this.companyTimer = null
          })
        }, this.typeInterval);
      } else {
        this.isLoading = false
      }
    },
  },
  created () {
    if (this.listingID) {
      this.loadListing()
    }
  },
  methods: {
    enhancedClickHandler (item, e) {
      console.log(item)
      console.log(e.currentTarget)
    },
    loadListing () {
      this.busy = true
      this.get(
          {
            path: `/api/v/1/job-referral/getListingDetails/${this.listingID}`,
            headers: {
              headers: { Authorization: `Bearer ${this.accessToken.access_token}` },
            },
          }
        ).then(resp => {
          this.setNewCompany(resp.data.company)
          this.jobTitle = resp.data.rawTitle
          this.jobDescription = resp.data.description
          this.faqs = resp.data.faqs?.replace(/(<([^>]+)>)/gi, '')
          this.validUntil = new Date(resp.data.until).toISOString().substring(0, 10)

          if (this.faqs?.length) {
            this.showFaqsField = true
          }
        })
        .finally(() => this.busy = false)
    },
    tinyMCEEventHandler (editor) {
      editor.on('init', e => {
        this.tinyMCEReady = true
        this.$nextTick().then(() => {
          const targetNode = document.querySelector('.tox-silver-sink')
          const MutationObserver = window.MutationObserver || window.WebKitMutationObserver
          const observer = new MutationObserver(mutationList => {
            for (const mutation of mutationList) {
              if (mutation.type === "childList") {
                this.linkModalOn = Boolean(targetNode.childNodes.length)
                  && Array.from(targetNode.firstChild.classList).includes('tox-dialog-wrap')
              }
            }
          })
          observer.observe(targetNode, { attributes: true, childList: true, subtree: true })
        })
      })
    },
    setNewCompany (item) {
      const company = !item || item.target
        ? {
            id: 0,
            title: this.search,
            link: 'The company will be created after you post your listing',
          }
        : {
            id: item.id,
            title: item.title,
            link: item.link?.url,
          }

      this.items = [company]
      this.company = company.id
    },
    onSubmit () {
      if (!this.listingID) {
        this.doCreate()
      } else {
        this.doUpdate()
      }
    },
    doCreate () {
      if (this.busy) return
      this.busy = true
      const data = new FormData()

      if (this.company) {
        data.append('companyId', this.company)
      }
      
      data.append('companyTitle', this.items.find(x => x.id === this.company)?.title)
      
      if (this.jobTitle) {
        data.append('job_title', this.jobTitle)
      }

      if (this.jobDescription) {
        data.append('job_desc', this.jobDescription)
      }

      if (this.faqs) {
        data.append('faqs', this.faqs)
      }

      if (this.validUntil) {
        data.append('until', this.validUntil)
      }

      this
        .post({
          path: '/api/v/1/job-referral/createJobOpportunity',
          data: data,
          headers: {
            headers: { Authorization: `Bearer ${this.access_token.access_token}` },
          },
        })
        .then(() => {
          this.$emit('on-complete')
        })
        .catch(error => {
          console.log(error)
          if (error.response && error.response.data && error.response.data.message) {
            alert(error.response.data.message)
          }
        })
        .finally(() => this.busy = false)
    },
    doDelete () {
      if (this.busy) return
      if (confirm('Are you sure you want to delete this job listing?')) {
        this.busy = true

        this
          .delete({
            path: `/api/v/1/job-referral/deleteListing/${this.listingID}`,
            headers: {
              headers: { Authorization: `Bearer ${this.access_token.access_token}` },
            },
          })
          .then(() => {
            this.$emit('on-complete', this.listingID)
          })
          .catch(error => {
            console.log(error)
            if (error.response && error.response.data && error.response.data.message) {
              alert(error.response.data.message)
            }
          })
          .finally(() => this.busy = false)
      }
    },
    doUpdate () {
      if (this.busy) return
      this.busy = true

      this
        .put({
          path: `/api/v/1/job-referral/updateJobOpportunity/${this.listingID}`,
          data: {
            companyId: this.company, 
            companyTitle: this.items.find(x => x.id === this.company)?.title, 
            job_title: this.jobTitle, 
            job_desc: this.jobDescription, 
            faqs: this.faqs, 
            until: this.validUntil, 
          },
          headers: {
            headers: { Authorization: `Bearer ${this.access_token.access_token}` },
          },
        })
        .then(() => {
          this.$emit('on-complete')
        })
        .catch(error => {
          console.log(error)
          if (error.response && error.response.data && error.response.data.message) {
            alert(error.response.data.message)
          }
        })
        .finally(() => this.busy = false)
    },
  },
}
</script>
<style lang="scss" scoped>
.form-job-creation {
  position: relative;
  background-color: white;
  &__inner {
    &:not(.visible) {
      visibility: hidden;
      pointer-events: none;
      opacity: 0;
      position: fixed;
    }

    &__header {
      padding: 2rem 0 16px;
      background-color: white;
      position: sticky;
      top: 0;
      z-index: 10;
    }

    &__footer {
      position: sticky;
      bottom: 0;
      background-color: white;

      &.pt-4 {
        border-top: 1px solid #d1d1d1;
      }

      &.mt-4 {
        .col {
          border-top: 1px solid #d1d1d1;
        }
      }
    }
  }
}
.inprogress-bar {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: calc(100% - 4rem);
}
</style>