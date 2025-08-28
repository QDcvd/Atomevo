// 引入权限缓存

// import store from '@/store';
// 引入页面
const AutoMartini = () =>
  import(
    /* webpackChunkName: "auto-martini" */ "@/pages/tools/auto-martini/auto-martini"
  );
const AutoDock = () =>
  import(/* webpackChunkName: "autodock" */ "@/pages/tools/autodock/autodock");
const AutoDockVina = () =>
  import(
    /* webpackChunkName: "autodock-vina" */ "@/pages/tools/autodock-vina/autodock-vina"
  );
const Commol = () =>
  import(/* webpackChunkName: "commol" */ "@/pages/tools/commol/commol");
const Dssp = () =>
  import(/* webpackChunkName: "dssp" */ "@/pages/tools/dssp/dssp");
const GMmpbsa = () =>
  import(/* webpackChunkName: "g-mmpbsa" */ "@/pages/tools/g-mmpbsa/g-mmpbsa");
const GMmpbsaAnalysis = () =>
  import(
    /* webpackChunkName: "g-mmpbsa-analysis" */ "@/pages/tools/g-mmpbsa-analysis/g-mmpbsa-analysis"
  );
const Gromacs = () =>
  import(/* webpackChunkName: "gromacs" */ "@/pages/tools/gromacs/gromacs");
const Ledock = () =>
  import(/* webpackChunkName: "ledock" */ "@/pages/tools/ledock/ledock");
const Martinize = () =>
  import(
    /* webpackChunkName: "martinize" */ "@/pages/tools/martinize/martinize"
  );
const Martinize3 = () =>
  import(
    /* webpackChunkName: "martinize" */ "@/pages/tools/martinize3.0/martinize"
  );
const Mktop = () =>
  import(/* webpackChunkName: "mktop" */ "@/pages/tools/mktop/mktop");
const Openbabel = () =>
  import(
    /* webpackChunkName: "openbabel" */ "@/pages/tools/openbabel/openbabel"
  );
const Plants = () =>
  import(/* webpackChunkName: "plants" */ "@/pages/tools/plants/plants");
const Plip = () =>
  import(/* webpackChunkName: "plip" */ "@/pages/tools/plip/plip");
const Procheck = () =>
  import(/* webpackChunkName: "procheck" */ "@/pages/tools/procheck/procheck");
const TrRosetta = () =>
  import(
    /* webpackChunkName: "tr-rosetta" */ "@/pages/tools/tr-rosetta/tr-rosetta"
  );
const Xscore = () =>
  import(/* webpackChunkName: "xscore" */ "@/pages/tools/xscore/xscore");
const XvgToCsv = () =>
  import(
    /* webpackChunkName: "xvg-to-csv" */ "@/pages/tools/xvg-to-csv/xvg-to-csv"
  );
const GMX = () =>
  import(/* webpackChunkName: "g-m-x" */ "@/pages/tools/gmx/gmx");
const Tksa = () =>
  import(/* webpackChunkName: "tksa" */ "@/pages/tools/tksa/tksa");
const RGB = () => import(/* webpackChunkName: "rgb" */ "@/pages/tools/rgb/rgb");
const GLAPD = () =>
  import(/* webpackChunkName: "glapd" */ "@/pages/tools/glapd/glapd");
const Modeller = () =>
  import(/* webpackChunkName: "modeller" */ "@/pages/tools/modeller/modeller");
const Gzeronine = () =>
  import(
    /* webpackChunkName: "gzeronine" */ "@/pages/tools/gzeronine/gzeronine"
  );
const Multiwfn = () =>
  import(/* webpackChunkName: "gzeronine" */ "@/pages/tools/multiwfn/multiwfn");
const Exp4Cas9 = () =>
  import(
    /* webpackChunkName: "gzeronine" */ "@/pages/tools/exp4Cas9/exp4Cas9.vue"
  );
// 引入组件
const PdbTool = () =>
  import(
    /* webpackChunkName: "gzeronine" */ "@/pages/tools/pdb-tool/pdb-tool.vue"
  );
const PrimerDesign = () =>
  import(
    /* webpackChunkName: "gzeronine" */ "@/pages/tools/PrimerDesign/PrimerDesign.vue"
  );

const FoldXAlaScan = () =>
  import(
    /* webpackChunkName: "foldxalaScan" */ "@/pages/tools/FoldXAlaScan/FoldXAlaScan.vue"
  );
const ClustalW2 = () =>
  import(
    /* webpackChunkName: "ClustalW2" */ "@/pages/tools/ClustalW2/ClustalW2.vue"
  );
const ToolsRoutes = [
  {
    path: "/PDB-tool",// 路由路径就是后端约定好的app_list中
    name: "PDB-tool",
    component: PdbTool,// 引入的组件
    meta: {
      title: "PDB-tool",
      roles: ["user"],// 角色控制，代表只有users权限的用户才能看
    },
  },
  {
    path: "/auto-martini", // 路由路径就是后端约定好的app_list中
    name: "auto-martini",
    component: AutoMartini, // 引入的组件
    meta: {
      title: "Auto-Martini",
      roles: ["user"],// 角色控制，代表只有users权限的用户才能看
    },
  },
  {
    path: "/autodock",
    name: "autodock",
    component: AutoDock,
    meta: {
      title: "AutoDock4",
      roles: ["user"],
    },
  },
  {
    path: "/autodock-vina",
    name: "autodock-vina",
    component: AutoDockVina,
    meta: {
      title: "AutoDock-Vina",
      roles: ["user"],
    },
  },
  {
    path: "/commol",
    name: "commol",
    component: Commol,
    meta: {
      title: "Commol",
      roles: ["user"],
    },
  },
  {
    path: "/dssp",
    name: "dssp",
    component: Dssp,
    meta: {
      title: "Dssp",
      roles: ["user"],
    },
  },
  {
    path: "/g-mmpbsa",
    name: "g-mmpbsa",
    component: GMmpbsa,
    meta: {
      title: "GMmpbsa",
      roles: ["user"],
    },
  },
  {
    path: "/g-mmpbsa-analysis",
    name: "g-mmpbsa-analysis",
    component: GMmpbsaAnalysis,
    meta: {
      title: "G-Mmpbsa-Analysis",
      roles: ["user"],
    },
  },
  {
    path: "/gromacs",
    name: "gromacs",
    component: Gromacs,
    meta: {
      title: "Gromacs",
      roles: ["user"],
    },
  },
  {
    path: "/ledock",
    name: "ledock",
    component: Ledock,
    meta: {
      title: "Ledock",
      roles: ["user"],
    },
  },
  {
    path: "/martinize",
    name: "martinize",
    component: Martinize,
    meta: {
      title: "Martinize",
      roles: ["user"],
    },
  },
  {
    path: "/martinize3.0",
    name: "martinize3.0",
    component: Martinize3,
    meta: {
      title: "Martinize3.0",
      roles: ["user"],
    },
  },
  {
    path: "/mktop",
    name: "mktop",
    component: Mktop,
    meta: {
      title: "Mktop",
      roles: ["user"],
    },
  },
  {
    path: "/openbabel",
    name: "openbabel",
    component: Openbabel,
    meta: {
      title: "Openbabel",
      roles: ["user"],
    },
  },
  {
    path: "/plants",
    name: "plants",
    component: Plants,
    meta: {
      title: "Plants",
      roles: ["user"],
    },
  },
  {
    path: "/plip",
    name: "plip",
    component: Plip,
    meta: {
      title: "Plip",
      roles: ["user"],
    },
  },
  {
    path: "/procheck",
    name: "procheck",
    component: Procheck,
    meta: {
      title: "Procheck",
      roles: ["user"],
    },
  },
  {
    path: "/tr-rosetta",
    name: "tr-rosetta",
    component: TrRosetta,
    meta: {
      title: "trRosetta",
      roles: ["user"],
    },
  },
  {
    path: "/xscore",
    name: "xscore",
    component: Xscore,
    meta: {
      title: "Xscore",
      roles: ["user"],
    },
  },
  {
    path: "/xvg-to-csv",
    name: "xvg-to-csv",
    component: XvgToCsv,
    meta: {
      title: "XvgToCsv",
      roles: ["user"],
    },
  },
  {
    path: "/gmx",
    name: "gmx",
    component: GMX,
    meta: {
      title: "G-M-X",
      roles: ["user"],
    },
  },
  {
    path: "/tksa",
    name: "tksa",
    component: Tksa,
    meta: {
      title: "Tksa",
      roles: ["user"],
    },
  },
  {
    path: "/rgb",
    name: "rgb",
    component: RGB,
    meta: {
      title: "RGB",
      roles: ["user"],
    },
  },
  {
    path: "/glapd",
    name: "glapd",
    component: GLAPD,
    meta: {
      title: "GLAPD",
      roles: ["user"],
    },
  },
  {
    path: "/modeller",
    name: "modeller",
    component: Modeller,
    meta: {
      title: "modeller",
      roles: ["user"],
    },
  },
  {
    path: "/gzeronine",
    name: "gzeronine",
    component: Gzeronine,
    meta: {
      title: "gzeronine",
      roles: ["user"],
    },
  },
  {
    path: "/multiwfn",
    name: "multiwfn",
    component: Multiwfn,
    meta: {
      title: "multiwfn",
      roles: ["user"],
    },
  },
  {
    path: "/exp4Cas9",
    name: "exp4Cas9",
    component: Exp4Cas9,
    meta: {
      title: "exp4Cas9",
      roles: ["user"],
    },
  },

  {
    path: "/Primer-Design",
    name: "Primer-Design",
    component: PrimerDesign,
    meta: {
      title: "Primer-Design",
      roles: ["user"],
    },
  },
  {
    path: "/FoldX-AlaScan",
    name: "FoldX-AlaScan",
    component: FoldXAlaScan,
    meta: {
      title: "FoldX-AlaScan",
      roles: ["user"],
    },
  },
  {
    path: "/Clustal-W2",
    name: "Clustal-W2",
    component: ClustalW2,
    meta: {
      title: "ClustalW2",
      roles: ["user"],
    },
  },
];

export default ToolsRoutes;
