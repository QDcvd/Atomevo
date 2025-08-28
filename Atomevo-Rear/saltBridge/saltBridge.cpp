#include <SBL/Models/Atom_with_flat_info_traits.hpp>
#include <SBL/Models/PDB_file_loader.hpp>
#include <SBL/CSB/Salt_bridges_finder.hpp>
typedef SBL::Models::T_Atom_with_flat_info_traits<>      Particle_traits;
typedef Particle_traits::Molecular_system                Molecular_system;
typedef SBL::Models::T_PDB_file_loader<Molecular_system> File_loader;
typedef SBL::CSB::T_Salt_bridges_finder<Particle_traits> Salt_bridges_finder;
int main(int argc, char *argv[])
{
  if(argc < 2)
      return -1;
  
  
  File_loader loader;
  loader.set_loaded_water(false);
  loader.add_input_file_name(argv[1]);
  loader.load(true, std::cout);
  
  Salt_bridges_finder finder;
  finder.add_residues(loader.get_geometric_model(0).residues_begin(),
                      loader.get_geometric_model(0).residues_end());
  finder.find_salt_bridges();
  std::ofstream out("pointwise-interactions.xml");
  {
    boost::archive::xml_oarchive ar(out);
    ar & boost::serialization::make_nvp("Salt_bridges_finder", finder);
  }
  out.close();
}