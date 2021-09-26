<template>
  <div class="membership-section">
    <h2 class="mb-4">CITANZ Membership</h2>
    <v-progress-linear
      v-if="!sectionDataLoaded"
      indeterminate
      rounded
      height="6"
    ></v-progress-linear>
    <template v-else>
      <p>
        <template v-if="user.usedToBeAMember || user.isPaidMember"><strong>My member ID</strong>: {{ user.citaID }}</template>
        <template v-else>Feel like to join?</template>
      </p>
      <p class="text-vertical-center" v-if="user.isPaidMember">
        <template v-if="user.neverExpire">You are a perpetual member. No renewal is required.</template>
        <span v-else>Your membership ends on <u>{{ user.expiry }}</u>.</span>
      </p>
      <p v-else-if="user.usedToBeAMember">
        <span>Your membership has <strong>expired</strong> on <u>{{ user.expiry }}</u>.</span>
      </p>
      <p v-if="user.canRenew || !user.isPaidMember">
        <template v-for="subscription in sectionData.subscriptions">
          <v-btn
            :disabled="processing"
            :loading="processing"
            v-for="variant in subscription.variants"
            :key="`subscription-${variant.id}`"
            @click.prevent="doSubscription(variant.id)"
            depressed
            color="primary"
          >
            {{ variant.variant_title }} - <strike style="margin-right: 0.25em;" v-if="variant.originalPrice">{{ variant.originalPrice }}</strike> {{ variant.price_label }}
          </v-btn>
        </template>
      </p>
      <p v-if="sectionData.discountDesc" v-html="sectionData.discountDesc"></p>

      <div class="student-extr-info" v-if="user.isStudent && !user.isRealStudent">
        <v-divider class="mt-4 mb-5"></v-divider>
        <h3 class="mb-3">Student Discount</h3>
        <p><small>Since you've claimed yourself as a student, here is a good news for you:</small></p>
        <p>CITANZ offers a <strong>50% off discount</strong> to students on our membership.</p>
        <p v-if="!user.hasPendingStudentApplication">To become a verified student and get the 50% discount, please submit a photo of the student ID.</p>
        <v-form
          v-if="!user.hasPendingStudentApplication"
          method="post"
          :disabled="busy"
          @submit.prevent="doSubmit"
        >
          <div class="file-upload-holder">
            <file-pond
              name="studentIDPhoto"
              :disabled="busy"
              ref="pond"
              label-idle="Drop or browse your files here..."
              :allow-multiple="false"
              accepted-file-types="image/jpeg, image/png"
              :server="server"
              :captureMethod="null"
              :instantUpload="false"
              :credits="null"
              :allowProcess="false"
              :allowRevert="true"
            />
          </div>
            <div class="d-flex justify-space-between">
              <p class="pr-4 mb-0"><small>Once the application is approved, the discount will be automatically applied.</small></p>
              <v-btn
                :disabled="busy"
                :loading="busy"
                type="submit"
                depressed
                color="primary"
              >Submit</v-btn>
            </div>
        </v-form>
        <template v-else>
          <v-divider class="mb-4"></v-divider>
          <p class="text-h5">Your application is being approved. You will receive an email when the approval is granted.</p>
        </template>
      </div>

      <v-dialog
        v-model="dialog"
        max-width="360"
        :persistent="lockDialog"
      >
        <form-payment
          ref="paymentForm"
          @submitting="lockDialog = true"
          @stripeTokenGranted="submitStripePayment"
        />
      </v-dialog>
      <v-dialog
        v-if="missingAddress"
        v-model="showAddressForm"
        max-width="360"
      >
        <form-just-address
          ref="justAddressForm"
          @submit="addressSubmitted"
          @stripeTokenGranted="submitStripePayment"
        />
      </v-dialog>
    </template>
  </div>
</template>

<script>
import { mapGetters, mapActions } from 'vuex'
import memberMixin from '../../../../mixins/memberMixin'
import PaymentForm from '../../../blocks/forms/PaymentForm'
import JustAddressForm from '../../../blocks/forms/JustAddressForm'
import vueFilePond from 'vue-filepond'
import 'filepond/dist/filepond.min.css'
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css'
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type'
import FilePondPluginImagePreview from 'filepond-plugin-image-preview'

const FilePond = vueFilePond(
  FilePondPluginFileValidateType,
  FilePondPluginImagePreview
)

export default {
  name: 'membership-section',
  components: {
    'form-payment': PaymentForm,
    'form-just-address': JustAddressForm,
  },
  mixins: [ memberMixin ],
  data() {
    return {
      dialog: false,
      lockDialog: false,
      showAddressForm: false,
      addressObj: null,
      pendingVariantID: null,
      processing: false,
      busy: false,
    }
  },
  computed: {
    ...mapGetters(['user', 'access_token']),
    missingAddress() {
      if (this.user) {
        return this.user.addressMissing && !this.addressObj
      }

      return true
    },
    requestHeaders() {
      return {
          Authorization: `Bearer ${this.access_token.access_token}`,
        }
    },
    server() {
      return {
        url: `/api/v/1/sd-application/`,
        process: {
          url: 'attach',
          headers: this.requestHeaders,
        }
      }
    },
  },
  watch: {
    showAddressForm(nv) {
      if (nv) {
        window.addEventListener('keydown', this.keydownHandler)
      } else {
        window.removeEventListener('keydown', this.keydownHandler)
      }
    },
    refreshingToken() {
        this.loadSectionData()
    },
    dialog(nv) {
      if (!nv) {
        this.processing = false
      }
    },
  },
  created() {
    window.removeEventListener('keydown', this.keydownHandler)
    this.loadSectionData()
  },
  methods: {
    ...mapActions(['post', 'setStripeKey', 'setUser', 'setAccessToken']),
    doSubmit() {
      if (this.busy) {
        return
      }

      this.busy = true
      this.post({
        path: '/api/v/1/sd-application',
        data: {},
        headers: {
          headers: this.requestHeaders,
        },
      }).then(resp => {
        const user = resp.data.user
        this.$refs.pond.processFiles().then(() => {
          this.setUser(user)
          this.busy = false
        })
      })
    },
    keydownHandler(e) {
      if (e.target.type !== 'submit' && e.key == 'Enter') {
        e.preventDefault()
        e.stopPropagation()
        e.stopImmediatePropagation()
        return false
      }
    },
    addressSubmitted(address) {
      this.addressObj = address
      this.showAddressForm = false
      this.doSubscription(this.pendingVariantID)
    },
    loadSectionData() {
      if (this.refreshingToken || !this.accessToken) {
        return
      }

      this.get(
        {
          path: `/api/v/1/member/getMembershipData`,
          headers: {
            headers: { Authorization: `Bearer ${this.accessToken.access_token}` },
          },
        }
      ).then(resp => {
        this.sectionDataLoaded = true
        this.setUser(resp.data.user)
        this.sectionData = resp.data
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
    submitStripePayment(token) {
      const data = new FormData()
      data.append('token', token)
      this.post({
        path: '/api/v/1/member/payMembership',
        data: data,
        headers: {
          headers: { Authorization: `Bearer ${this.access_token.access_token}` },
        },
      }).then(resp => {
        this.$refs.paymentForm.dismissSubmitting()
        this.processing = this.dialog = this.lockDialog = false

        this.$store.dispatch('setShowModal', true)
        this.$store.dispatch('setModalColor', 'primary')
        this.$store.dispatch(
          'setPostbackMessage',
          '<p>Your payment has gone through. You can view your payment history at <br /><strong>Member centre</strong> > <strong>Payments</strong></p>'
        )

        this.sectionDataLoaded = false
        this.loadSectionData()
      }).catch(error => {
        this.$refs.paymentForm.dismissSubmitting()
        this.processing = this.dialog = this.lockDialog = false
        this.$store.dispatch('setShowModal', true)
        this.$store.dispatch('setModalColor', 'red')
        this.$store.dispatch('setPostbackMessage', error.response && error.response.data ? error.response.data.message : 'Uknown error')
        this.busy = false
      })
    },
    doSubscription(id) {
      this.processing = true
      if (this.missingAddress) {
        this.pendingVariantID = id
        this.showAddressForm = true
        setTimeout(() => {
          this.$refs.justAddressForm.refocus()
        }, 100)
        return
      }

      const data = new FormData()
      data.append('id', id)

      if (this.addressObj) {
        data.append('address', JSON.stringify(this.addressObj))
      }

      this.pendingVariantID = this.addressObj = null

      this.post({
        path: '/api/v/1/member/prepareMembership',
        data: data,
        headers: {
          headers: { Authorization: `Bearer ${this.access_token.access_token}` },
        },
      }).then(resp => {
        this.setStripeKey(resp.data.stripe_key)
        this.setUser(resp.data.user)
        this.$nextTick().then(() => {
          const amount = resp.data.payable_total < 0 ? 0 : resp.data.payable_total
          if (amount > 0) {
            this.$refs.paymentForm.setAmount(amount)
          } else {
            alert('Something went wrong - the amount to pay cannot be 0')
          }
        })
        this.dialog = true
      }).catch(error => {
        this.$store.dispatch('setShowModal', true)
        this.$store.dispatch('setModalColor', 'red')
        this.$store.dispatch('setPostbackMessage', error.response && error.response.data ? error.response.data.message : 'Uknown error')
      })
    },
  }
}
</script>
