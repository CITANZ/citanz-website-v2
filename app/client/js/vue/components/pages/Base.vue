<template>
<v-main>
    <component v-if="site_data" :is="site_data.pagetype" />
</v-main>
</template>

<script>
import * as components from "./index.js"
export default {
    name: "Base",
    components: { ...components },
}
</script>
