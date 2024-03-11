<template>
<v-form
  class="form-profile"
  method="post"
  @submit.prevent="updateProfile"
  :disabled="busy"
  autocomplete="off"
>
  <v-progress-linear
    v-if="!userProfileLoaded"
    indeterminate
    rounded
    height="6"
  ></v-progress-linear>
  <fieldset v-else :disabled="busy">
    <p>Please complete your information as much as possible.</p>
    <h3 class="mb-4">Bio</h3>
    <v-text-field
      v-model="firstName"
      :error-messages="firstNameError"
      label="First name*"
      required
      @input="$v.firstName.$touch()"
      @blur="$v.firstName.$touch()"
    ></v-text-field>
    <v-text-field
      v-model="lastName"
      :error-messages="lastNameError"
      label="Last name*"
      required
      @input="$v.lastName.$touch()"
      @blur="$v.lastName.$touch()"
    ></v-text-field>

    <v-menu
      v-model="showCalendar"
      :close-on-content-click="false"
      transition="scale-transition"
      max-width="290"
      min-width="auto"
    >
      <template v-slot:activator="{ on, attrs }">
        <v-text-field
          v-model="formattedDob"
          :error-messages="dobError"
          label="D.o.B*"
          required
          @input="$v.dob.$touch()"
          @blur="$v.dob.$touch()"
          readonly
          v-bind="attrs"
          v-on="on"
        ></v-text-field>
      </template>
      <v-date-picker
        v-model="dob"
        no-title
        dateFormat="dd/MM/yyy"
        @input="showCalendar = false"
      ></v-date-picker>
    </v-menu>

    <v-radio-group label="Gender*" v-model="gender" row>
      <v-radio
        label="Female"
        value="Female"
      ></v-radio>
      <v-radio
        label="Male"
        value="Male"
      ></v-radio>
      <v-radio
        label="Other"
        value="Other"
      ></v-radio>
    </v-radio-group>
    <h3 class="mt-6 mb-4">Contact</h3>
    <v-text-field
      class="mb-6"
      v-model="email"
      :error-messages="emailError"
      label="Email*"
      required
      @input="$v.email.$touch()"
      @blur="$v.email.$touch()"
      persistent-hint
      hint="BE NOTED: This also changes the email that you use for signin!"
    ></v-text-field>
    <v-text-field
      v-model="phone"
      :error-messages="phoneError"
      label="Contact number"
      required
      @input="$v.phone.$touch()"
      @blur="$v.phone.$touch()"
    ></v-text-field>
    <vuetify-google-autocomplete
      id="addressFinder"
      label="Address"
      append-icon="mdi-magnify"
      @placechanged="getAddressData"
      @focus="disableChromeAutofill"
      ref="addressField"
      placeholder=" "
      v-model="address"
      autocomplete="new-password"
    ></vuetify-google-autocomplete>
    <h3 class="mt-6 mb-4">Occupation</h3>
    <v-radio-group label="Are you a student?" v-model="isStudent" row>
      <v-radio
        label="Yes"
        :value="true"
      ></v-radio>
      <v-radio
        label="No"
        :value="false"
      ></v-radio>
    </v-radio-group>
    <v-text-field
      v-model="organisation"
      :label="isStudent ? 'School name*' : 'Company name*'"
      required
    ></v-text-field>
    <v-row v-if="isStudent">
      <v-col>
        <v-select
          label="What's your degree?"
          v-model="degree"
          :items="degrees"
        ></v-select>
      </v-col>
      <v-col>
        <v-combobox
          v-model="major"
          label="What's your major?"
          :items="majors"
          hint="type in if it's not supplied in the list"
          persistent-hint
        ></v-combobox>
      </v-col>
    </v-row>
    <v-row v-else>
      <v-col>
        <v-select
          label="What's your title"
          :items="titlLevels"
          v-model="titleLevel"
        ></v-select>
      </v-col>
      <v-col>
        <v-combobox
          label="Your job category?"
          :items="jobCategories"
          v-model="jobCategory"
          hint="type in if it's not supplied in the list"
          persistent-hint
        ></v-combobox>
      </v-col>
    </v-row>
    <template v-if="user?.isPaidMember">
      <h3 class="mt-6">My C.V</h3>
      <p>Store your CV & cover letter upfront, so they will be ready upon your applications for any referral opportunities 🤞</p>
      <p><em>Please rest assured: your CV and cover letter are <strong>NOT</strong> going to be displayed to other members without your authorisation.</em></p>
      <v-row>
        <v-col cols="12">
          <v-row v-if="!cvUrl?.length || editingCV" align="center">
            <v-col>
              <v-file-input
                show-size
                v-model="cv"
                accept=".pdf,.doc,.docx"
                label="C.V"
              ></v-file-input>
            </v-col>
            <v-col v-if="cvUrl?.length" cols="auto"><v-btn @click.prevent="editingCV = !editingCV" text small>cancel</v-btn></v-col>
          </v-row>
          <p class="mt-4" v-else>
            <v-btn color="secondary" :href="cvUrl" target="_blank">
              <v-icon left>
                mdi-file-pdf-box
              </v-icon>
              My Curriculum Vitae
            </v-btn>
            <v-btn class="ml-2" @click.prevent="editingCV = !editingCV" text small>re-upload</v-btn>
            <v-btn class="ml-2" @click.prevent="deleteCV" text small color="error">delete</v-btn>
          </p>
          <v-row v-if="!clUrl?.length || editingCL" align="center">
            <v-col>
              <v-file-input
                show-size
                v-model="cl"
                accept=".pdf,.doc,.docx"
                label="Cover letter"
              ></v-file-input>
            </v-col>
            <v-col v-if="clUrl?.length" cols="auto"><v-btn @click.prevent="editingCL = !editingCL" text small>cancel</v-btn></v-col>
          </v-row>
          <p class="mt-4" v-else>
            <v-btn color="secondary" :href="clUrl" target="_blank">
              <v-icon left>
                mdi-file-pdf-box
              </v-icon>
              My Cover Letter
            </v-btn>
            <v-btn class="ml-2" @click.prevent="editingCL = !editingCL" text small>re-upload</v-btn>
            <v-btn class="ml-2" @click.prevent="deleteCL" text small color="error">delete</v-btn>
          </p>
        </v-col>
      </v-row>
    </template>
    <h3 class="mt-6">Social</h3>
    <v-row>
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="wechatID"
          label="WeChat ID"
          prepend-inner-icon="mdi-wechat"
        ></v-text-field>
      </v-col>
      <v-col cols="12" sm="6">
        <v-text-field
          v-model="github"
          label="Github ID"
          prepend-inner-icon="mdi-github"
        ></v-text-field>
      </v-col>
      <v-col cols="12">
        <v-text-field
          v-model="linkedinLink"
          label="LinkedIn link"
          prepend-inner-icon="mdi-linkedin"
        ></v-text-field>
      </v-col>
    </v-row>
    <v-row justify="end" align="center">
      <v-col cols="auto" v-if="!busy">
        <v-btn type="reset" @click.prevent="reset" x-small text>reset</v-btn>
      </v-col>
      <v-col cols="auto">
        <v-btn type="submit" :loading="busy" depressed color="primary">Submit</v-btn>
      </v-col>
    </v-row>
  </fieldset>
</v-form>
</template>

<script>
import { mapGetters, mapActions } from 'vuex'
import { validationMixin } from 'vuelidate'
import { required, email } from 'vuelidate/lib/validators'

export default {
  name: 'form-profile',
  mixins: [validationMixin],
  validations: {
    firstName: { required },
    lastName: { required },
    gender: { required },
    dob: { required },
    email: { required, email },
    phone: { required },
  },
  emits: ['data-loaded'],
  props: {
    accessToken: Object,
  },
  watch: {
    dob (nv) {
      const [year, month, day] = nv.split('-')
      this.formattedDob = `${day}/${month}/${year}`
    },
    refreshingToken (nv) {
      if (!nv) {
        this.getFullProfile()
      }
    },
    editingCV () {
      this.cv = null
    },
    editingCL () {
      this.cl = null
    },
  },
  data() {
    return {
      busy: false,
      userProfileLoaded: false,
      showCalendar: false,
      firstName: null,
      lastName: null,
      gender: null,
      dob: null,
      formattedDob: null,
      email: null,
      phone: null,
      address: null,
      suburb: null,
      city: null,
      region: null,
      country: null,
      postcode: null,
      isStudent: false,
      organisation: null,
      degree: null,
      major: null,
      titleLevel: null,
      jobCategory: null,
      originalData: null,
      wechatID: null,
      linkedinLink: null,
      github: null,
      cv: null,
      cl: null,
      editingCV: false,
      editingCL: false,
      deletingCV: false,
      deletingCL: false,
      cvUrl: null,
      clUrl: null,
    }
  },
  computed: {
    ...mapGetters(['refreshingToken', 'user']),
    titlLevels() {
      return [
        'Entry',
        'Junior',
        'Intermedia',
        'Senior',
        'Lead',
      ]
    },
    degrees() {
      return [
        'Certificate',
        'Diploma',
        'Bachelor',
        'Post-graduate',
        'PhD',
      ]
    },
    majors() {
      return [
        'Computer Science',
        'Data Analyst',
        'Engineering',
        'Information System',
        'Management',
      ]
    },
    jobCategories() {
      return [
        'BA / BI / Project Management',
        'Design',
        'Engineering',
        'HR',
        'Marketing',
        'Systems / Ops / DBA',
        'Web / Programming',
        'Testing/QA',
        'Data Analyst',
      ]
    },
    firstNameError () {
      const errors = []
      if (!this.$v.firstName.$dirty) return errors
      !this.$v.firstName.required && errors.push('First name is required.')
      return errors
    },
    lastNameError () {
      const errors = []
      if (!this.$v.lastName.$dirty) return errors
      !this.$v.lastName.required && errors.push('Last name is required.')
      return errors
    },
    genderError () {
      const errors = []
      if (!this.$v.gender.$dirty) return errors
      !this.$v.gender.required && errors.push('Gender is required.')
      return errors
    },
    dobError () {
      const errors = []
      if (!this.$v.dob.$dirty) return errors
      !this.$v.dob.required && errors.push('D.o.B is required.')
      return errors
    },
    emailError () {
      const errors = []
      if (!this.$v.email.$dirty) return errors
      !this.$v.email.email && errors.push('Must be valid email')
      !this.$v.email.required && errors.push('Email is required')
      return errors
    },
    phoneError () {
      const errors = []
      if (!this.$v.phone.$dirty) return errors
      !this.$v.phone.required && errors.push('Contact number is required.')
      return errors
    },
    postData() {
      return {
        FirstName: this.firstName,
        LastName: this.lastName,
        Email: this.email,
        Phone: this.phone,
        Dob: this.dob,
        Gender: this.gender,
        isStudent: this.isStudent,
        Organisation: this.organisation,
        Degree: this.degree,
        Major: this.major,
        TitleLevel: this.titleLevel,
        JobCategory: this.jobCategory,
        WechatID: this.wechatID,
        LinkedInLink: this.linkedinLink,
        Github: this.github,
        CV: this.cvUrl,
        CoverLetter: this.clUrl,
        DeletingCV: this.deletingCV,
        DeletingCL: this.deletingCL,
        Address: JSON.stringify({
          Address: this.address,
          Suburb: this.suburb,
          City: this.city,
          Region: this.region,
          Country: this.country,
          Postcode: this.postcode,
        }),
      }
    }
  },
  created() {
    this.getFullProfile()
  },
  methods: {
    ...mapActions(['get', 'post', 'setUser', 'setAccessToken', 'uploadToBucket', 'deleteFromBucket', 'getBucketObjectHeader']),
    // fuck Chrome - thanks, but no fanks
    disableChromeAutofill() {
      if (!this.$refs.addressField) return
      const field = this.$refs.addressField.$el.querySelector('[name="addressFinder"]')
      field.setAttribute('autocomplete', 'new-password')
      field.removeAttribute('placeholder')
    },
    getAddressData(addressData, placeResultData) {
      this.suburb = placeResultData ? placeResultData.vicinity : null
      this.city = addressData ? addressData.locality : null
      this.region = addressData ? addressData.administrative_area_level_1 : null
      this.country = addressData ? addressData.country : null
      this.postcode = addressData ? addressData.postal_code : null
    },
    getFullProfile() {
      if (this.refreshingToken || !this.accessToken) {
        return
      }

      this.get(
        {
          path: '/api/v/1/member/getFullProfile',
          headers: {
            headers: { Authorization: `Bearer ${this.accessToken.access_token}` },
          },
        }
      ).then(resp => {
        this.userProfileLoaded = true
        this.originalData = {...resp.data.full, ...resp.data.basic}
        this.setUser(resp.data.basic)
        this.$emit('data-loaded', resp.data.basic)
        this.reset()
      }).catch(error => {
        if (error.response && error.response.data && error.response.data.code === 401) {
          this.setAccessToken(null)
          this.setUser(null)
          this.$router.replace('/')
        } else if (error.response && error.response.data && error.response.data.message) {
          alert(error.response.data.message)
        }
      })
    },
    reset() {
      this.firstName = this.originalData.first_name
      this.lastName = this.originalData.last_name
      this.dob = this.originalData.dob
      this.gender = this.originalData.gender
      this.email = this.originalData.email
      this.phone = this.originalData.phone
      this.address = this.originalData.address.address
      this.suburb = this.originalData.address.suburb
      this.city = this.originalData.address.city
      this.region = this.originalData.address.region
      this.country = this.originalData.address.country
      this.postcode = this.originalData.address.postcode
      this.isStudent = this.originalData.isStudent
      this.organisation = this.originalData.organisation
      this.degree = this.originalData.degree
      this.major = this.originalData.major
      this.titleLevel = this.originalData.titleLevel
      this.jobCategory = this.originalData.jobCategory
      this.wechatID = this.originalData.wechatID
      this.linkedinLink = this.originalData.linkedinLink
      this.github = this.originalData.github
      this.cvUrl = this.originalData.cv
      this.clUrl = this.originalData.cl
      this.editingCL = this.editingCV = this.deletingCL = this.deletingCV = false
      this.cv = this.cl = null
    },
    async updateProfile() {
      if (this.busy) {
        return false
      }

      this.busy = true

      await Promise.all([this.uploadCV(), this.uploadCL()])

      const data = new FormData()

      for (let key in this.postData) {
        if (this.postData[key] !== null) {
          data.append(key, this.postData[key])
        }
      }

      this.post(
        {
          path: '/api/v/1/member/setProfile',
          data: data,
          headers: {
            headers: { Authorization: `Bearer ${this.accessToken.access_token}` },
          },
        }
      ).then(resp => {
        this.$store.dispatch('setShowModal', true)
        this.$store.dispatch('setModalColor', 'primary')
        this.$store.dispatch('setPostbackMessage', resp.data.message)
        this.originalData = {...resp.data.profile, ...resp.data.user}
        this.setUser(resp.data.user)
        this.reset()
      }).catch(error => {
        this.$store.dispatch('setShowModal', true)
        this.$store.dispatch('setModalColor', 'red')
        this.$store.dispatch('setPostbackMessage', error.response && error.response.data ? error.response.data.message : 'Uknown error')
      }).finally(() => {
        this.busy = false
        this.deletingCV = this.deletingCL = false
      })
    },
    uploadCV () {
      return new Promise((resolve, reject) => {
        if (this.cv) {
          this
            .performCVDeletion()
            .finally(() => {
              this
                .uploadToBucket({ file: this.cv, id: this.user.id })
                .then(resp => {
                  this.cvUrl = resp.key
                  resolve()
                }).catch(reject)
            })
        } else {
          resolve()
        }
      })
    },
    uploadCL () {
      return new Promise((resolve, reject) => {
        if (this.cl) {
          this
            .performCLDeletion()
            .finally(() => {
              this
                .uploadToBucket({ file: this.cl, id: this.user.id })
                .then(resp => {
                  this.clUrl = resp.key
                  resolve()
                }).catch(reject)
            })
        } else {
          resolve()
        }
      })
    },
    deleteCV () {
      if (confirm('Are you sure you want to delete your CV?')) {
        this
          .performCVDeletion()
          .then(() => {
            this.deletingCV = true
            this.updateProfile()
          })
      }
    },
    deleteCL () {
      if (confirm('Are you sure you want to delete your cover letter?')) {
        this
          .performCLDeletion()
          .then(() => {
            this.deletingCL = true
            this.updateProfile()
          })
      }
    },
    performCVDeletion() {
      return new Promise(resolve => {
        if (!this.cvUrl?.length) {
          resolve()
        } else {
          const key = this.cvUrl.replace('https://citanz.s3.ap-southeast-2.amazonaws.com/', '')
          this
            .getBucketObjectHeader(key)
            .then(data => {
              if (this.user.id != null && data.Metadata['owner-uuid'] === this.user.id) {
                this
                  .deleteFromBucket(key)
                  .finally(resolve)
              } else {
                alert('YOU DO NOT HAVE PERMISSION TO PERFORM THIS ACTION!')
                reject()
              }
            })
            .catch( err => {
              console.log(err)
            })
        }
      })
    },
    performCLDeletion() {
      return new Promise((resolve, reject) => {
        if (!this.clUrl?.length) {
          resolve()
        } else {
          const key = this.clUrl.replace('https://citanz.s3.ap-southeast-2.amazonaws.com/', '')

          this
            .getBucketObjectHeader(key)
            .then(data => {
              if (this.user.id != null && data.Metadata['owner-uuid'] === this.user.id) {
                this
                  .deleteFromBucket(key)
                  .finally(resolve)
              } else {
                alert('YOU DO NOT HAVE PERMISSION TO PERFORM THIS ACTION!')
                reject()
              }
            })
            .catch( err => {
              if (err.statusCode === 404) {
                resolve()
              } else {
                alert('Something went wrong! Please contact CITANZ admin in Wechat group!')
                reject()
              }
            })
        }
      })
    },
  },
}
</script>
