const DataStatistics = () => import(/* webpackChunkName: "dataStatistics" */ '@/pages/common/dataStatistics/dataStatistics.vue')

const IndexRoutes = [
  {
    path: '/dataStatistics',
    name: 'dataStatistics',
    component: DataStatistics,
    meta: {
      title: '数据中心'
    }
  },
]

export default IndexRoutes