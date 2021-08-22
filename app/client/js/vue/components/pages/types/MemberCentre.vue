<template>
  <div class="page-content">
    <section-title />
    <v-container>
      <v-row>
        <v-col cols="12" sm="4" md="3" tag="nav">
          <v-list v-if="user">
            <v-list-item-group>
              <v-list-item
                v-for="(item, i) in site_data.memberMenu"
                :key="`member-menuitem-${i}`"
                :to="item.url"
              >
                <v-list-item-icon v-if="item.icon">
                  <v-icon v-text="item.icon"></v-icon>
                </v-list-item-icon>
                <v-list-item-content>
                  <v-list-item-title v-text="item.title"></v-list-item-title>
                </v-list-item-content>
              </v-list-item>
              <v-list-item
                @click.prevent="doSignout"
              >
                <v-list-item-icon>
                  <v-icon v-text="'mdi-logout'"></v-icon>
                </v-list-item-icon>
                <v-list-item-content>
                  <v-list-item-title v-text="'Sign out'"></v-list-item-title>
                </v-list-item-content>
              </v-list-item>
            </v-list-item-group>
          </v-list>
          <v-list v-else>
            <v-list-item-group>
              <v-list-item
                to="/member/me"
              >
                <v-list-item-icon>
                  <v-icon v-text="'mdi-account-circle'"></v-icon>
                </v-list-item-icon>
                <v-list-item-content>
                  <v-list-item-title v-text="'Sign in'"></v-list-item-title>
                </v-list-item-content>
              </v-list-item>
            </v-list-item-group>
          </v-list>
        </v-col>
        <v-col cols="12" sm="8" md="9">
          <signin-form v-if="!user" />
          <form-activation v-if="user && !user.verified" :accessToken="access_token" @activated="onAccountActivated" />
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script>
import SigninForm from '../../blocks/forms/SigninForm'
import ActivationForm from '../../blocks/forms/ActivationForm'
import { mapGetters, mapActions } from 'vuex'

export default {
  name: 'MemberCentre',
  components: {
    'signin-form': SigninForm,
    'form-activation': ActivationForm,
  },
  created() {
    console.log(this.user)
  },
  computed: {
    ...mapGetters(['access_token', 'user'])
  },
  methods: {
    ...mapActions(['setAccessToken', 'setUser']),
    doSignout() {
      if (confirm('You sure?')) {
        this.setAccessToken(null)
        this.setUser(null)
      }
    },
    onAccountActivated(payload) {
      console.log(payload)
      this.setUser(payload);
    }
  }
}
</script>
