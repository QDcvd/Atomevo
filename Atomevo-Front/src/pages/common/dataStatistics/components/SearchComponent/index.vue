<template>
  <div class="search-content">
    <div class="custom-tabs">
        <template v-for="(item, index) in tabs">
          <div
              @click="selectIndex(index)"
              class="custom-tabs-item"
              :class="tabIndex === index ? 'tabs-item-active':''"
              :key="item.id">{{ item.name }}</div>
        </template>
    </div>

    <el-date-picker
        v-model="datePickerData"
        size="small"
        type="daterange"
        range-separator="至"
        start-placeholder="开始日期"
        end-placeholder="结束日期"
    />

  </div>
</template>

<script>
export default {
  name: "SearchComponent",
  data() {
    return {
      datePickerData: '',
      tabIndex: 0,
      tabs: [{
        id: 1,
        name: '实时',
      },{
        id: 2,
        name: '最近7天'
      },{
        id: 3,
        name: '最近30天'
      }],
    }
  },
  methods: {
    selectIndex(index) {
      this.tabIndex = index
    }
  }
}
</script>

<style lang="less" scoped>
  .search-content {
    display: flex;
    align-items: center;

    .custom-tabs {
      display: flex;
      margin-right: 8px;

      .custom-tabs-item {
        font-size: 14px;
        padding: 0 16px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(0, 0, 0, 0.15);
      }

      .tabs-item-active {
        background-color: #409EFF;
        border-color: #409EFF;
        color: #FFF;
      }
    }
  }
</style>