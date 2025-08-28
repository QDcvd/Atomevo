<template>
  <div>
    <div class="system-content">
      <h1>系统</h1>

      <search-component />

      <div class="data-Board-content">
        <el-tabs type="border-card">
          <el-tab-pane label="访问人数" :lazy="true">
              <ve-line :data="chartData" />
          </el-tab-pane>
          <el-tab-pane label="CPU负载">配置管理</el-tab-pane>
          <el-tab-pane label="任务数">角色管理</el-tab-pane>
        </el-tabs>
      </div>
    </div>
  </div>
</template>

<script>
import SearchComponent from './components/SearchComponent'

export default {
  name: "dataStatistics",
  components: {
    SearchComponent
  },
  data() {
    return {
      dataEmpty: true,
      chartData: {
        columns: ["日期", "访问用户", "下单用户"],
        rows: [
          { 日期: "1/1", 访问用户: 1391, 下单用户: 1093, 下单率: 0.32 },
          { 日期: "1/2", 访问用户: 3530, 下单用户: 3230, 下单率: 0.26 },
          { 日期: "1/3", 访问用户: 2923, 下单用户: 2623, 下单率: 0.76 },
          { 日期: "1/4", 访问用户: 1723, 下单用户: 1423, 下单率: 0.49 },
          { 日期: "1/5", 访问用户: 3792, 下单用户: 3492, 下单率: 0.323 },
          { 日期: "1/6", 访问用户: 4593, 下单用户: 4293, 下单率: 0.78 }
        ]
      }
    }
  },
}
</script>

<style lang="less" scoped>
  .system-content {

    .data-Board-content {
      margin-top: 1.1rem;
    }
  }
</style>