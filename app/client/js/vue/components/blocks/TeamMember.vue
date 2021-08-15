<template>
<div class="working-group__team-member">
  <v-img
    v-if="memberData.portrait"
    :src="memberData.portrait.url"
    :aspect-ratio="memberData.portrait.width / memberData.portrait.height"
  />
  <h4>{{ memberData.title }}</h4>
  <p class="subheading">{{ memberData.jobtitle }}</p>
  <p class="subheading">
    <v-btn
      fab
      :href="memberData.linkedin"
      target="_blank"
      x-small
      depressed
    >
      <v-icon small dense>mdi-linkedin</v-icon>
    </v-btn>
  </p>
</div>
</template>

<script>
export default {
  name: 'team-member',
  props: {
    memberData: Object,
  }
}
</script>
