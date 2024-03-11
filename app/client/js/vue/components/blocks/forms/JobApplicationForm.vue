<template>
  <v-form
    class="form-job-application px-8 pb-8"
    method="post"
    @submit.prevent="onSubmit"
    :disabled="busy || !userProfile"
  >
    <v-progress-linear
      v-show="busy || !userProfile"
      indeterminate
      rounded
      height="6"
      class="inprogress-bar"
    ></v-progress-linear>
    <div v-if="!busy" class="form-job-application__inner visible">
      <template v-if="!userProfile?.isPaidMember">
        <h2 class="form-title form-job-application__inner__header">Wait a minute...</h2>
        <p>You are not an active member. Our referral opportunities are for active members only.</p>
        <p>
          <v-btn to="/member/membership" color="primary">Activate Membership</v-btn>
        </p>
      </template>
      <template v-else>
        <h2 class="form-title form-job-application__inner__header">Application Form</h2>
        <p>Fill as much as you can, and good luck job hunting!</p>
        <v-row>
          <v-col cols="12" sm="6">
            <v-text-field hide-details="auto" label="First name*" :rules="[generalRules.required]" v-model="userProfile.first_name"></v-text-field>
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field hide-details="auto" label="Last name*" :rules="[generalRules.required]" v-model="userProfile.last_name"></v-text-field>
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field hide-details="auto" label="Email address*" :rules="[ generalRules.required, generalRules.email ]" v-model="userProfile.email"></v-text-field>
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field hide-details="auto" label="Contact number" v-model="userProfile.phone"></v-text-field>
          </v-col>
          <v-col cols="12">
            <v-text-field hide-details="auto" label="LinkedIn*" :rules="[generalRules.required]" v-model="userProfile.linkedinLink"></v-text-field>
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field hide-details="auto" label="Github ID" v-model="userProfile.github"></v-text-field>
          </v-col>
          <v-col cols="12" sm="6">
            <v-text-field hide-details="auto" label="WeChat ID*" :rules="[generalRules.required]" v-model="userProfile.wechatID"></v-text-field>
          </v-col>
          <v-col cols="12">
            <v-row v-if="!cv && !cvUrl?.length" align="center">
              <v-col>
                <v-file-input
                  show-size
                  v-model="cv"
                  accept=".pdf,.doc,.docx"
                  label="C.V"
                  :rules="[generalRules.required]"
                ></v-file-input>
              </v-col>
              <v-col v-if="userProfile.cv?.length" cols="auto"><v-btn @click.prevent="cvUrl = userProfile.cv" text small>Use pre-uploaded</v-btn></v-col>
            </v-row>
            <p class="mt-4" v-else>
              <v-btn color="secondary" :href="userProfile.cv" target="_blank">
                <v-icon left>
                  mdi-file-pdf-box
                </v-icon>
                My Curriculum Vitae
              </v-btn>
              <v-btn class="ml-2" @click.prevent="changeCV" text small>Change</v-btn>
            </p>
            <v-row v-if="!cl && !clUrl?.length" align="center">
              <v-col>
                <v-file-input
                  show-size
                  v-model="cl"
                  accept=".pdf,.doc,.docx"
                  label="Cover letter"
                  :rules="[generalRules.required]"
                ></v-file-input>
              </v-col>
              <v-col v-if="userProfile.cl?.length" cols="auto"><v-btn @click.prevent="clUrl = userProfile.cl" text small>Use pre-uploaded</v-btn></v-col>
            </v-row>
            <p class="mt-4" v-else>
              <v-btn color="secondary" :href="userProfile.cl" target="_blank">
                <v-icon left>
                  mdi-file-pdf-box
                </v-icon>
                My Cover Letter
              </v-btn>
              <v-btn class="ml-2" @click.prevent="changeCL" text small>Change</v-btn>
            </p>
          </v-col>
        </v-row>
        <v-row class="form-job-application__inner__footer pt-4" no-gutters>
          <v-col><p class="caption mb-0 grey--text font-italic">By pushing the <strong class="primary--text">SUBMIT</strong> button, you also agree to our Referral Opportunity policy.</p></v-col>
          <v-col cols="auto">
            <v-btn @click.prevent="$emit('close-form')">Cancel</v-btn>
          </v-col>
          <v-col cols="12" sm="auto">
            <p class="ml-4"><v-btn type="submit" color="primary">Submit</v-btn></p>
          </v-col>
        </v-row>
      </template>
    </div>
  </v-form>
</template>
<script>
  import memberMixin from '../../../mixins/memberMixin'
  
  export default {
    name: 'job-application-form',
    mixins: [ memberMixin ],
    emits: ['on-complete', 'close-form'],
    data () {
      return {
        busy: false,
        userProfile: null,
        cv: null,
        cl: null,
        cvUrl: null,
        clUrl: null,
      }
    },
    computed: {
      generalRules () {
        return {
          required: value => !!value || 'Required.',
          email: value => {
            const pattern = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
            return pattern.test(value) || 'Invalid e-mail.'
          },
        }
      },
    },
    watch: {
      
    },
    created () {
      this.loadUser()
    },
    methods: {
      changeCL () {
        if (!this.cl) {
          this.clUrl = null
        } else {
          this.cl = null
        }
      },
      changeCV () {
        if (!this.cv) {
          this.cvUrl = null
        } else {
          this.cv = null
        }
      },
      loadUser () {
        this.busy = true
        this.get({
            path: `/api/v/1/member/getFullProfile`,
            headers: {
              headers: { Authorization: `Bearer ${this.accessToken.access_token}` },
            },
          }).then(resp => {
            this.userProfile = { ...resp.data?.basic, ...resp.data?.full }
            console.log(this.userProfile)
          }).finally(() => this.busy = false)
      },
      onSubmit () {
        if (this.busy) return

        this.busy = true

        const data = new FormData()
        data.append('FirstName', this.userProfile.first_name)
        data.append('LastName', this.userProfile.last_name)
        data.append('Email', this.userProfile.email)
        data.append('LinkedIn', this.userProfile.linkedinLink)
        data.append('WechatID', this.userProfile.wechatID)
        data.append('JobId', this.site_data.id)
        
        if (this.userProfile.phone?.length) {
          data.append('Phone', this.userProfile.phone)
        }

        if (this.userProfile.github?.length) {
          data.append('Github', this.userProfile.github)
        }

        if (this.cv) {
          data.append('UploadedCV', this.cv)
        } else {
          data.append('UseExistingCV', this.cvUrl?.length ? true : false)
        }

        if (this.cl) {
          data.append('UploadedCL', this.cl)
        } else {
          data.append('UseExistingCL', this.clUrl?.length ? true : false)
        }

        this
          .post({
            path: '/api/v/1/job-referral/applyForReferralJob',
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
    },
  }
</script>
<style lang="scss" scoped>
  .form-job-application {
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