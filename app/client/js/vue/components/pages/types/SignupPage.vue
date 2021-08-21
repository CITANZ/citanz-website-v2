<template>
<div class="page-content">
  <section-title />
  <page-hero v-if="site_data.hero" :hero="site_data.hero" />
  <v-container>
    <v-row>
      <v-col cols="12" sm="10" md="7" offset-sm="1">
        <article class="typography" v-html="site_data.content"></article>
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="12" sm="6" offset-sm="3">
        <form method="post" @submit.prevent="doSubmit" novalidate>
          <fieldset :disabled="busy">
            <v-text-field
              v-model="firstName"
              :error-messages="firstNameError"
              label="First name"
              required
              @input="$v.firstName.$touch()"
              @blur="$v.firstName.$touch()"
            ></v-text-field>
            <v-text-field
              v-model="lastName"
              :error-messages="lastNameError"
              label="Last name"
              required
              @input="$v.lastName.$touch()"
              @blur="$v.lastName.$touch()"
            ></v-text-field>
            <v-text-field
              v-model="email"
              :error-messages="emailErrors"
              label="Email"
              required
              @input="$v.email.$touch()"
              @blur="$v.email.$touch()"
            ></v-text-field>
            <v-text-field
              v-model="password"
              :append-icon="passVisible ? 'mdi-eye' : 'mdi-eye-off'"
              :type="passVisible ? 'text' : 'password'"
              :error-messages="passwordError"
              label="Password"
              required
              @input="$v.password.$touch()"
              @blur="$v.password.$touch()"
              @click:append="passVisible = !passVisible"
            ></v-text-field>
            <v-row>
              <v-col>
                <v-checkbox
                  class="my-0"
                  v-model="agreed"
                  :error-messages="checkboxErrors"
                  required
                  @change="$v.agreed.$touch()"
                  @blur="$v.agreed.$touch()"
                >
                  <span slot="label">I have read and accepted the <a href="#" target="_blank">T&amp;C</a></span>
                </v-checkbox>
              </v-col>
              <v-col cols="auto">
                <v-btn type="submit"
                  :loading="busy"
                  depressed
                >Signup</v-btn>
              </v-col>
            </v-row>
          </fieldset>
        </form>
      </v-col>
    </v-row>
    <v-dialog
      v-model="showModal"
      max-width="320"
    >
      <v-card>
        <v-toolbar
          color="red"
          dark
          flat
          dense
        >{{ modalColor == 'red' ? 'Error' : 'Message' }}</v-toolbar>
        <v-card-text class="pt-4" v-html="postbackMessage"></v-card-text>
      </v-card>
    </v-dialog>
  </v-container>
</div>
</template>
<script>
import { validationMixin } from 'vuelidate'
import { required, email } from 'vuelidate/lib/validators'
export default {
  name: 'SignupPage',
  mixins: [validationMixin],
  validations: {
    firstName: { required },
    lastName: { required },
    email: { required, email },
    password: { required },
    agreed: {
      checked (val) {
        return val
      },
    },
  },
  data() {
    return {
      firstName: null,
      lastName: null,
      email: null,
      password: null,
      passVisible: false,
      agreed: false,
      busy: false,
      showModal: false,
      postbackMessage: null,
      modalColor: 'primary',
    }
  },
  watch: {
    showModal(nv) {
      if (!nv) {
        this.postbackMessage = null
      }
    }
  },
  computed: {
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
    emailErrors () {
      const errors = []
      if (!this.$v.email.$dirty) return errors
      !this.$v.email.email && errors.push('Must be valid email')
      !this.$v.email.required && errors.push('Email is required')
      return errors
    },
    passwordError () {
      const errors = []
      if (!this.$v.password.$dirty) return errors
      !this.$v.password.required && errors.push('Password is required.')
      return errors
    },
    checkboxErrors () {
      const errors = []
      if (!this.$v.agreed.$dirty) return errors
      !this.$v.agreed.checked && errors.push('You must agree to continue!')
      return errors
    },
  },
  methods: {
    doSubmit() {
      this.$v.$touch()
      if (!this.$v.$invalid) {
        this.busy = true
        const path = `${this.$route.path}/do-signup`
        const data = new FormData()

        if (this.firstName) {
          data.append('firstName', this.firstName)
        }

        if (this.lastName) {
          data.append('lastName', this.lastName)
        }

        if (this.password) {
          data.append('password', this.password)
        }

        if (this.email) {
          data.append('email', this.email)
        }

        if (this.agreed !== null && this.agreed !== undefined) {
          data.append('agreed', this.agreed ? 1 : 0)
        }

        this.$store.dispatch('getCSRFToken', path).then(resp => {
          if (resp.data.csrf) {
            this.$store.dispatch('doSignup', {
              path: path,
              data: data,
              headers: {
                headers: {
                  'X-CSRF-TOKEN': resp.data.csrf,
                }
              },
            }).then(resp => {
              this.busy = false
              this.showModal = true
              this.modalColor = 'primary'
              this.postbackMessage = resp.data.message
            }).catch(error => {
              this.busy = false
              this.showModal = true
              this.modalColor = 'red'
              if (error.response && error.response.data) {
                this.postbackMessage = error.response.data
              } else {
                this.postbackMessage = 'Uknown error'
              }
            })
          }
        })
      }
    }
  }
}
</script>
