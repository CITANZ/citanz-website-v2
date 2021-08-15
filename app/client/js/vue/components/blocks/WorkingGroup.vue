<template>
<div class="working-group">
  <h3 class="mb-12">{{ groupData.title }}</h3>
  <v-row class="mb-18">
    <v-col
      v-for="(member, i) in groupData.members"
      :key="`team-${groupIndex}-member-${i}`"
      cols="12"
      sm="4"
      md="3"
    >
      <team-member :memberData="member" />
    </v-col>
  </v-row>
</div>
</template>

<script>
import TeamMember from './TeamMember'
export default {
  name: 'workking-group',
  components: {
    'team-member': TeamMember,
  },
  props: {
    groupIndex: Number,
    groupData: Object,
  }
}
</script>
