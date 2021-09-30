<template>
  <div class="payments-section">
    <h2 class="mb-4">Security</h2>
    <v-progress-linear
      v-if="!sectionDataLoaded"
      indeterminate
      rounded
      height="6"
    ></v-progress-linear>
    <template v-else>
      <h3 class="mt-8 mb-4">My QR Code</h3>
      <v-row>
        <v-col cols="auto">
          <qrcode-vue :value="sectionData.qrString" size="128" level="H" />
        </v-col>
        <v-col :cols="null" md="5">
          <p>Your cita member's QR code can be used for scanning at our offline events, or used for express signin (coming later)</p>
        </v-col>
      </v-row>
      <h3 class="mt-8 mb-2">Update Password</h3>
      <v-form method="post" autocomplete="off" @submit.prevent="doSubmit">
        <fieldset :disabled="busy">
          <v-row align="center">
            <v-col :cols="null" md="6">
              <v-text-field
                v-model="password"
                :append-icon="passVisible ? 'mdi-eye' : 'mdi-eye-off'"
                :type="passVisible ? 'text' : 'password'"
                :error-messages="passwordError"
                label="Password"
                required
                @click:append="passVisible = !passVisible"
              ></v-text-field>
            </v-col>
            <v-col cols="auto">
              <v-btn :disabled="busy" :loading="busy" depressed type="submit">
                Update
              </v-btn>
            </v-col>
          </v-row>
        </fieldset>
      </v-form>
    </template>
  </div>
</template>

<script>
import memberMixin from '../../../../mixins/memberMixin'
import { validationMixin } from 'vuelidate'
import { required } from 'vuelidate/lib/validators'
import QrcodeVue from 'qrcode.vue'

export default {
  name: 'security-section',
  mixins: [ memberMixin, validationMixin ],
  validations: {
    password: { required },
  },
  components: { QrcodeVue },
  data() {
    return {
      busy: false,
      password: null,
      passVisible: false,
    }
  },
  computed: {
    passwordError () {
      const errors = []
      if (!this.$v.password.$dirty) return errors
      !this.$v.password.required && errors.push('Password is required.')
      return errors
    },
  },
  created() {
    this.fetchSectionData()
  },
  methods: {
    fetchSectionData() {
      if (!this.user) {
        this.$router.replace('/member')
        return
      }

      this.get(
        {
          path: `/api/v/1/member/getSecuritySectionData`,
          headers: {
            headers: { Authorization: `Bearer ${this.accessToken.access_token}` },
          },
        }
      ).then(resp => {
        this.sectionDataLoaded = true
        this.sectionData = resp.data
      })
    },
    doSubmit() {
      this.$v.$touch()
      if (!this.$v.$invalid) {
        if (this.busy) {
          return
        }

        this.busy = true
        const data = new FormData()
        data.append('password', this.password)
        this.post(
          {
            path: `/api/v/1/member/updatePassword`,
            data: data,
            headers: {
              headers: { Authorization: `Bearer ${this.accessToken.access_token}` },
            },
          }
        ).then(resp => {
          this.busy = false
          this.$v.$reset()
          this.$store.dispatch('setShowModal', true)
          this.$store.dispatch('setModalColor', 'primary')
          this.$store.dispatch('setPostbackMessage', resp.data.message)
          this.password = null
          this.sectionData.qrString = resp.data.qrString
        }).catch(error => {
          this.busy = false
          this.$store.dispatch('setShowModal', true)
          this.$store.dispatch('setModalColor', 'red')
          this.$store.dispatch(
            'setPostbackMessage',
            error.response && error.response.data ? error.response.data.message : 'Uknown error'
          )
        })
      }
    }
  }
}
</script>
