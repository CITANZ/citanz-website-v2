<template>
<section class="section mailchimp-section">
  <v-container>
    <h2 class="text-center mb-4 text-h4">{{ mcData.title }}</h2>
    <v-row class="text-center mb-6">
      <v-col cols="12" md="6" offset-md="3">
        <div class="typography" v-html="mcData.content"></div>
      </v-col>
    </v-row>
    <v-row class="text-center">
      <v-col cols="12" md="10" offset-md="1">
        <div class="mailchimp">
          <p :class="'help d-block d-sm-none is-' + message.type" v-if="message.text" v-html="message.text"></p>
          <v-form ref="form" class="mailchimp-form" @submit.prevent="submit">
            <v-row justify="center">
              <v-col cols="12" md="auto">
                <v-text-field
                  solo
                  :rules="[rules.required]"
                  label="First Name"
                  v-model="firstname"
                  dense
                  depressed
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="auto">
                <v-text-field
                  solo
                  :rules="[rules.required]"
                  label="Last Name"
                  v-model="lastname"
                  dense
                  depressed
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="auto">
                <v-text-field
                  solo
                  :rules="[rules.required, rules.email]"
                  label="Email Address"
                  type="email"
                  v-model="email"
                  dense
                  depressed
                ></v-text-field>
              </v-col>
              <v-col cols="12" sm="auto">
                <v-btn color="primary" type="submit" :loading="is_loading" depressed>Stay in the loop</v-btn>
              </v-col>
            </v-row>
          </v-form>
          <p :class="'help mb-0 d-none d-sm-block is-' + message.type" v-if="message.text" v-html="message.text"></p>
        </div>
      </v-col>
    </v-row>
  </v-container>
</section>
</template>

<script>
import axios from 'axios'

export default {
  name: 'mail-chimp',
  props: {
    mcData: Object,
  },
  data() {
    return {
      email       :   null,
      firstname   :   null,
      lastname    :   null,
      is_loading  :   false,
      message     :   { type: 'success', text: null},
      rules: {
        required: value => !!value || 'Required.',
        counter: value => value.length <= 20 || 'Max 20 characters',
        email: value => {
          const pattern = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
          return pattern.test(value) || 'Invalid e-mail.'
        },
      },
    }
  },
  methods: {
    submit() {
      if (this.is_loading) return false

      this.is_loading     =   true
      this.message.text   =   null
      let me      =   this,
          data    =   new FormData()
      data.append('email', this.email)
      data.append('firstname', this.firstname)
      data.append('lastname', this.lastname)
      axios.post(
          '/api/v/1/subscribe',
          data
      ).then((resp) => {
          me.is_loading   =   false
          me.message.type =   'success'
          me.message.text =   resp.data
          me.email        =   null
          me.firstname    =   null
          me.lastname     =   null
          this.$refs.form.reset()
      }).catch((error) => {
          me.is_loading   =   false
          if (error.response.data.message) {
              me.message.type =   'danger'
              me.message.text =   error.response.data.message
          }
      })
    }
  }
}
</script>
