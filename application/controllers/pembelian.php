<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
//
//Class name: Pembelian controller
//version : 1.0
//Author : Iswan Putera


class Pembelian extends CI_Controller{
	function __construct(){
		parent::__construct();
		$this->load->model("Admin_model");
		$this->load->model("inv_model");
		$this->load->model("purch_model");
		$this->load->library('zetro_auth');
		$this->load->model("report_model");
		$this->load->helper("print_report");
	}
	
	function Header(){ 
	// load header
		$this->load->view('admin/header');	
	}
	
	function Footer(){
	//load footer
		$this->load->view('admin/footer');	
	}
	function list_data($data){
	//membentuk data array untuk dikirim saat load view
		$this->data=$data;
	}
	function View($view){
		$this->Header();
		$this->zetro_auth->view($view);
		$this->load->view($view,$this->data);	
		$this->Footer();
	}
	//tampilan awal ketika menu input pembelian di klik
	function index(){
		$data=array();
		$this->zetro_auth->menu_id(array('pembelian__index','pembelian__list_beli'));
		$this->list_data($this->zetro_auth->auth());
		$this->View('pembelian/material_income');
	}
	function return_beli(){
		$data=array();
		$this->zetro_auth->menu_id(array('pembelian__return_beli'));
		$this->list_data($this->zetro_auth->auth());
		$this->View('pembelian/material_income_return');
	}
	function nomor_transaksi(){
		$tipe=$_POST['tipe'];
		$nomor=$this->Admin_model->penomoran($tipe);
		echo $nomor;	
	}
	//menampilkan data - data sesuai dengan variable yang dikirim via ajx post
	
	function harga($harga){
		// insert harga jual pada data array
		$this->harga=$harga;
	}	
	function jenistran($jtran){
		//insert jenis transaksi penjualan (GI) atau pembelian(GR)
		$this->jtran=$jtran;
	}
	/*simpan data header transaksi*/
	function set_header_pembelian(){
		$data=array();$cek='';$datax=array();$total=0;
		$cek=$this->Admin_model->field_exists('inv_pembelian',"where NoUrut='".$_POST['no_trans']."' and Tanggal='".tgltoSql($_POST['tanggal'])."'",'ID');
		$supplier=explode(' - ',$_POST['pemasok']);
		$data['NoUrut']		=$_POST['no_trans'];
		$data['Tanggal']	=tgltoSql($_POST['tanggal']);
		$data['ID_Jenis']	=$_POST['cbayar'];	
		$data['ID_Pemasok']	=$_POST['id_pemasok'];	
		$data['Nomor']		=$_POST['faktur'];	
		$data['Bulan']		=substr($_POST['tanggal'],3,2);	
		$data['Tahun']		=substr($_POST['tanggal'],6,4);	
		$data['Deskripsi']	=(count($supplier)!=0)?addslashes($supplier[0]):'';	
		$total=empty($_POST['total'])? 0:$_POST['total'];
		($cek=='')?
		$this->Admin_model->replace_data('inv_pembelian',$data):'';
		//$this->Admin_model->upd_data('inv_pembelian',"set ID_Bayar='".$total."'","where ID='$cek'");
		
		$datax['nomor']		=$_POST['no_trans'];
		$datax['jenis_transaksi']='GR';
		$this->Admin_model->replace_data('nomor_transaksi',$datax);
	}
	/*simpan data detail transaksi*/
	function set_detail_pembelian(){
		$data=array();$rcord=0;
		$id_beli=rdb('inv_pembelian','ID','ID',"where NoUrut='".$_POST['no_trans']."' and Tanggal='".tgltoSql($_POST['tanggal'])."'");
		//$rcord=empty($id_beli)?$this->Admin_model->total_data('inv_pembelian_detail',$id_beli,'ID_Beli');
		$data['tanggal']	=tgltoSql($_POST['tanggal']);
		$data['id_beli']	=$id_beli;
		$data['id_barang']	=rdb('inv_barang','ID','ID',"where Nama_Barang='".$_POST['nm_barang']."'");
		$data['jml_faktur']	=$_POST['jumlah'];
		$data['Jumlah']		=$_POST['harga_beli'];
		$data['Harga_Beli']	=round(($_POST['harga_beli']/$_POST['jumlah']),-1);
		$data['Bulan']		=$_POST['id_satuan'];
		$data['Tahun']		=date('yz');
		$data['Keterangan']	=$_POST['keterangan'];
		$this->Admin_model->replace_data('inv_pembelian_detail',$data);
	}
	function hapus_transaksi(){
	  //jika transaksi di batalkan
	  $data=array();
	  $ID=$_POST['ID'];
	  $this->Admin_model->hps_data('inv_pembelian_detail',"where ID='$ID'");	
	}
	
	function update_stock(){
		$data=array();$hasil_konv=1;
		$stock_awal=0;$stock_akhir=0;$rcord=0;	
		$id_barang=rdb('inv_barang','ID','ID',"where Nama_Barang='".$_POST['nm_barang']."'");
		$stock_awal=rdb('inv_material_stok','stock','stock',"where id_barang='".$id_barang."' and batch='".date('yz')."'");
		$hasil_konv=rdb('inv_konversi','isi_konversi','isi_konversi',"where id_barang='".$id_barang."' and sat_beli='".$_POST['id_satuan']."'");
		$stock_akhir=($stock_awal+($hasil_konv*$_POST['jumlah']));
		$data['id_barang']	=$id_barang;
		$data['nm_satuan']	=rdb('inv_barang','ID_Satuan','ID_Satuan',"where Nama_Barang='".$_POST['nm_barang']."'");
		$data['nm_barang']	=$_POST['nm_barang'];
		$data['batch']		=date('yz');
		$data['stock']		=$stock_akhir;
		$data['harga_beli']	=round($_POST['harga_beli']/$_POST['jumlah'],-2);
		$data['created_by']	=$this->session->userdata('userid');
		$this->Admin_model->replace_data('inv_material_stok',$data);
	}
	//list pembelian di mode input pembelian
	function show_list(){
		$data=array();$n=0;$id_beli='';
		$jtran=$_POST['no_transaksi'];
		$tanggal=tgltoSql($_POST['tanggal']);
		$id_beli=rdb('inv_pembelian','ID','ID',"where NoUrut='".$_POST['no_transaksi']."' and Tanggal='$tanggal'");
		if($id_beli){//!=''|| $id_beli!=0){
			$data=$this->Admin_model->show_list('inv_pembelian_detail',"where ID_Beli='$id_beli' order by ID");
			foreach ($data as $r){
				$n++;
				echo tr().td($n,'center').
						  //td(rdb('inv_barang','Kode','Kode',"where ID='".$r->ID_Barang."'")).
						  td(rdb('inv_barang','Nama_Barang','Nama_Barang',"where ID='".$r->ID_Barang."'")).
						  td(rdb('inv_barang_satuan','Satuan','Satuan',"where ID='".$r->Bulan."'")).
						  td(number_format($r->Jml_Faktur,2),'right').
						  td(number_format($r->Jumlah,2),'right').
						  td($r->Keterangan).
						  td("<img src='".base_url()."asset/images/no.png' title='Hapus transaksi' onclick=\"image_click('".$r->ID."','del');\">","center").
					 _tr();
			}
		}else{	echo tr().td('&nbsp;','left\' colspan=\'8\'')._tr();}
	}
	
	function get_pemasok(){
		$data=array();
		$str	=$_GET['str'];
		$limit	=$_GET['limit'];
		$data=$this->purch_model->get_pemasok($str,$limit);
		echo json_encode($data);
	}

//-------List Pembelian----------------
	function laporan_pembelian(){
	//process filter
	$data=array();$where='';$datax=array();$n=0;
	empty($_POST['dari_tanggal'])?
		$where="":
		$where="where Tanggal='".tgltoSql($_POST['dari_tanggal'])."'";
	empty($_POST['smp_tanggal'])?
		$where .="":
		$where="where (Tanggal between '".tgltoSql($_POST['dari_tanggal'])."' and '".tgltoSql($_POST['smp_tanggal'])."')"; 
	empty($_POST['nm_vendor'])?
		$where .='':
		$where .=" and ID_Pemasok='".$_POST['nm_vendor']."'";
		$data=$this->Admin_model->show_list('inv_pembelian',$where." order by NoUrut");
		echo $where;
		foreach($data as $r){
			$n++;$x=0;
			echo tr('xx list_genap').td($n.nbs(3),'center').td(tglfromSql($r->Tanggal),'center').td($r->NoUrut,'center').
					  td(strtoupper(rdb('inv_pemasok','Pemasok','Pemasok',"where ID='".$r->ID_Pemasok."'")),'left\' colspan=\'3\'').
					 // td(rdb('inv_pembelian_jenis','Jenis_Beli','Jenis_Beli',"where ID='".$r->ID_Jenis."'")).
					  td('<b>'.number_format($r->ID_Bayar,2).'</b>','right').
				 _tr();	
			$datax=$this->Admin_model->show_list('inv_pembelian_detail',"where ID_Beli='".$r->ID."'");
			foreach($datax as $row){
				$x++;
				echo tr().td(nbs(3).$x,'center\' colspan=\'2\'').
						 // td(rdb('inv_barang','Kode','Kode',"where ID='".$row->ID_Barang."'").nbs(5),'right\' colspan=\'2\'').
						  td(nbs(2).rdb('inv_barang','Nama_Barang','Nama_Barang',"where ID='".$row->ID_Barang."'"),'left').
						  td(nbs(5).rdb('inv_barang_satuan','Satuan','Satuan',"where ID='".$row->Bulan."'")).
						  td(number_format($row->Jml_Faktur,2),'right').
						  td(number_format($row->Harga_Beli,2),'right').
						  td(number_format($row->Jumlah,2),'right').
					 _tr();	
			}
		}
	}
	
	//----------------------------support function----------------------------------
	//get data material by kode
	function get_material_kode(){
		$data=array();
		$kode=$_POST['kode'];
		$data=$this->purch_model->get_material_kode($kode);
		echo (count($data)>0)?json_encode($data[0]):'{"Nama_Barang":"","ID_Satuan":""}';
	}
	
	function get_satuan_konversi(){
		$data=array();
		$kode=$_POST['nm_barang'];
		$data=$this->purch_model->get_satuan_konv($kode);
		foreach($data as $r){
			echo "<option value='".$r->sat_beli."'>".$r->Satuan."</option>";	
		}
	}
	function get_total_belanja(){
		$data		=array();$tt_harga=0;
		$no_trans	=$_POST['no_trans'];
		$tanggal	=$_POST['tanggal'];
		$data		=$this->purch_model->get_total_belanja($no_trans,$tanggal);
		foreach($data as $r){
			$tt_harga=$r->total;
		}
		echo $tt_harga;
	}
}