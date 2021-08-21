<template>
  <div class="page-content">
    <section-title />
    <v-container>
      <v-row>
        <v-col cols="12" sm="4" md="3" tag="nav">
          <v-list>
            <v-list-item-group>
              <v-list-item
                v-for="(item, i) in site_data.memberMenu"
                :key="`member-menuitem-${i}`"
                text
                :to="item.url"
                link
              >
                <v-list-item-icon v-if="item.icon">
                  <v-icon v-text="item.icon"></v-icon>
                </v-list-item-icon>
                <v-list-item-content>
                  <v-list-item-title v-text="item.title"></v-list-item-title>
                </v-list-item-content>
              </v-list-item>
            </v-list-item-group>
          </v-list>
        </v-col>
        <v-col cols="12" sm="8" md="9">

        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script>
export default {
  name: 'MemberCentre',
  created() {
    this.site_data.memberMenu.forEach((item, i) => {
      console.log(item)
    })
  }
}
</script>
