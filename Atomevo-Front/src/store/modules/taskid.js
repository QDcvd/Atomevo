const Task = {
  namespaced: true,
  state: {
    Auto_Martini: "", //Auto-Martini任务推送的id
    PROCHECK: "", // PROCHECK任务推送的id
    XVG2CSV: "", // XVG2CSVID任务推送的id
    AutoDock: "", // AUTODOCK任务推送的id
    AutoDock_Vina: "", // AUTODOCKVina任务推送的id
    Dssp: "", // DsspID任务推送的id
    Martinize_Protein: "", // MartinizeID任务推送的id
    Plip: "", // PlipID任务推送的id
    XScore: "", // XScoreID任务推送的id
    LEDock: "", // LEDockID任务推送的id
    OpenBabel: "", // OpenBabel任务推送的id
    Plants: "", // Plants任务推送的id
    Mktop: "", //MktopID 任务推送ID
    Commol: "", // Commol任务推送的id
    trRosetta: "", // trRosetta任务推送的id
    g_mmpbsa: "", //g_mmpbsa任务推送的id
    g_mmpbsa_analysis: "", //g_mmpbsa任务推送的id
    gromacs: "", //gromacs任务推送的id
    RGB: "", //RGB任务推送的id
    Tksa: "", //Tska任务推送的id
    Glapd: "", //Glapd任务推送的id
    Gmx: "", //Gmx任务推送的id
    Modeller: "", //Modeller任务推送的id
    Multiwfn: "", //Multiwfn任务推送的id
  },
  getter: {},
  mutations: {
    //配置任务id
    auto_martini_ID(state, id) {
      state.Auto_Martini = id;
    },
    procheck_ID(state, id) {
      state.PROCHECK = id;
    },
    xvg_to_csv_ID(state, id) {
      state.XVG2CSV = id;
    },
    autodock_ID(state, id) {
      state.AutoDock = id;
    },
    autodock_vina_ID(state, id) {
      state.AutoDock_Vina = id;
    },
    dssp_ID(state, id) {
      state.Dssp = id;
    },
    martinize_protein_ID(state, id) {
      state.Martinize_Protein = id;
    },
    plip_ID(state, id) {
      state.Plip = id;
    },
    xscore_ID(state, id) {
      state.XScore = id;
    },
    ledock_ID(state, id) {
      state.LEDock = id;
    },
    obabel_ID(state, id) {
      state.OpenBabel = id;
    },
    plants_ID(state, id) {
      state.Plants = id;
    },
    mktop_ID(state, id) {
      state.Mktop = id;
    },
    commol_ID(state, id) {
      state.Commol = id;
    },
    trrosetta_ID(state, id) {
      state.trRosetta = id;
    },
    g_mmpbsa_ID(state, id) {
      state.g_mmpbsa = id;
    },
    g_mmpbsa_analysis_ID(state, id) {
      state.g_mmpbsa_analysis = id;
    },
    gromacs_ID(state, id) {
      state.gromacs = id;
    },
    rgb_ID(state, id) {
      state.RGB = id;
    },
    tksa_ID(state, id) {
      state.Tksa = id;
    },
    glapd_ID(state, id) {
      state.Glapd = id;
    },
    gmx_ID(state, id) {
      state.Gmx = id;
    },
    modeller_ID(state, id) {
      state.Modeller = id;
    },
    multiwfn_ID(state, id) {
      state.Multiwfn = id;
    },
  },
};

export default Task;
