<template>
    <svg class="icon" aria-hidden="true">
        <use :xlink:href="iconName"></use>
    </svg>
</template>

<script>
  export default {
    name: "MagIcon",
    props: {
      iconClass: {
        type: String,
        required: true
      },
    },
    computed: {
      iconName() {
        return `#${this.iconClass}`
      }
    }
  }
</script>

<style lang="less">
    .icon {
        width: 1rem;
        height: 1rem;
        vertical-align: -0.25rem;
        fill: currentColor;
        overflow: hidden;
        margin-right: 0.2rem;
    }
</style>
