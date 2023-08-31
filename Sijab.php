<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'third_party/PDFMerger/PDFMerger.php';
use PDFMerger\PDFMerger; 
class Sijab extends CI_Controller
{

	function __construct()
	{
		parent::__construct();
		if ($this->ion_auth->logged_in() != true) {
			redirect('auth/logout');
		}

		$this->load->model(array('artikel_model', 'karyawan_model', 'dokumen_model', 'Sijab_model', 'Referensi_model', 'jabatan_model', 'organisasi_model'));
		$this->load->library(array('navigation'));
		$this->load->library('upload');
		$this->navigation->setMenuActive('beranda');
		// data hak akses
		$this->sess_hcm = unserialize($this->session->userdata('hcm'));
		$this->akses_hcm = $this->sess_hcm['role']['id'];
		$this->user = $this->session->userdata('session')['user'];
		$this->biodata = $this->session->userdata('session')['biodata_detail'];
	}

	public function index()
	{
		$this->navigation->setMenuActive('sijab');
		$this->navigation->setBreadcrumbSijab();
		$data['navbar'] = $this->navigation->getMenu();
		$data['breadcrumb'] = $this->navigation->getBreadcrumb();

		$data['icon'] = '<i class="fa fa-home"></i>';
		$data['title'] = 'home';
		$data['artikel'] = $this->artikel_model->view_by_date_now_show()->result();
		$data['legal'] = $this->dokumen_model->get_all_legal(array('is_dashboard' => 1));
		$data['info'] = $this->dokumen_model->get_all_hcinfo(null, array('is_dashboard' => 1));
		$data['css'] = array();
		$data['js'] = array('plugins/chartjs/Chart.1.1.js');
		$this->template->load('template', 'sijab/index', $data);
	}

	public function dashboard()
	{
		$this->navigation->setMenuActive('sijab');
		$this->navigation->setBreadcrumbSijab();
		$data['navbar'] = $this->navigation->getMenu();
		$data['breadcrumb'] = $this->navigation->getBreadcrumb();

		$data['jumlah_karyawan'] = $this->karyawan_model->chart_karyawan_by_status_karyawan();
		$data['icon'] = '<i class="fa fa-home"></i>';
		$data['title'] = 'Dashboard';
		$data['css'] = array();
		$data['js'] = array('plugins/chartjs/Chart.js');
		$this->template->load('template', 'sijab/dashboard', $data);
	}

	public function create()
	{
		$data['title'] = 'Tambah Sijab';
		$data['icon'] = '<i class="mega-octicon octicon-person"></i>';

		// jquery validation
		$this->jquery_validation->set_rules($this->rules);
		$this->jquery_validation->set_messages($this->message);

		// plugins
		$data['css'] = array('plugins/datepicker/datepicker3.css', 'plugins/select2/select2.css', 'plugins/iCheck/all.css');
		$data['js'] = array(
			'js/jquery.validate.js', 'js/additional-methods.js', 'plugins/iCheck/icheck.min.js', 'plugins/datepicker/bootstrap-datepicker.js',
			'plugins/input-mask/jquery.inputmask.js', 'plugins/input-mask/jquery.inputmask.date.extensions.js', 'plugins/input-mask/jquery.inputmask.extensions.js', 'plugins/select2/select2.js', 'plugins/bootstrap-filestyle/src/bootstrap-filestyle.min.js',
		);
		$data['jenis_sijab'] = $this->Sijab_model->get_jenis_sijab();
		$data['jabatan'] = $this->jabatan_model->viewall()->result();
		$data['subdit'] = $this->organisasi_model->viewall()->result();
		$data['level'] = $this->Referensi_model->get_level_karyawan()->result();
		$this->template->load('template', 'sijab/create', $data);	
	}

	public function view_cv($karyawan_id)
	{
		$karyawan = $this->Sijab_model->get_detail_karyawan($karyawan_id);
		$m_karyawan = $this->Sijab_model->get_m_karyawan($karyawan_id);

		$data['detail'] = $karyawan;
		$data['m_karyawan'] = $m_karyawan;

		$namafile = url_title($karyawan['nama'] . '-' . $karyawan['nik']);

		$this->load->library('Pdf');
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR); //aplikasinya
		$pdf->SetAuthor('PT. TELKOM SATELIT INDONESIA');
		$pdf->SetTitle('CV - ' . $karyawan['nama']);
		$pdf->SetSubject('CV');


		//tidak memakai header	
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(true);
		$pdf->SetFont('helvetica', 'false', 11, 'false');
		//set margin
		$pdf->SetMargins(20, 20, 20);
		// add a page
		$pdf->AddPage();

		$html = $this->load->view('sijab/view_cv', $data, true);

		// output the HTML content
		$pdf->writeHTML($html, true, false, true, false, '');
		$pdf->Output($namafile . '.pdf', 'I');
	}


	public function save()
	{

		$this->db->trans_start();
		$post = $this->input->post();

		$nomor = '….../D0.000/HCM.01/TSAT/MM.YY';

		$data = [
			// 'nomor' => $nomor,
			'tgl' => $post['tgl'],
			'm_karyawan_id' => $post['m_karyawan_id'],
			'from_posisi' => $post['from_posisi'],
			'from_bp' => $post['from_bp'],
			'from_subdit' => $post['from_subdit'],
			'to_posisi' => $post['to_posisi'],
			'to_bp' => $post['to_bp'],
			'to_subdit' => $post['to_subdit'],
			'r_jenis_sijab_id' => $post['status'],
			'keterangan' => $post['keterangan'],
			'm_karyawan_id_ketua' => $post['m_karyawan_id_ketua'],
			'm_karyawan_id_anggota_1' => ($post['m_karyawan_id_anggota_1']) ? $post['m_karyawan_id_anggota_1'] : NULL,
			'm_karyawan_id_anggota_2' => ($post['m_karyawan_id_anggota_2']) ? $post['m_karyawan_id_anggota_2'] : NULL,
			'm_karyawan_id_anggota_3' => ($post['m_karyawan_id_anggota_3']) ? $post['m_karyawan_id_anggota_3'] : NULL,
			'm_karyawan_id_anggota_4' => ($post['m_karyawan_id_anggota_4']) ? $post['m_karyawan_id_anggota_4'] : NULL,
			'm_karyawan_id_sekretaris' => ($post['m_karyawan_id_sekretaris']) ? $post['m_karyawan_id_sekretaris'] : NULL,
			'status' => 'Draft',
			'created_by' => $this->biodata->id,
			'created_date' => date('Y-m-d H:i'),
		];

		$id =  $this->Sijab_model->save($data);


		if ($post['status'] == '4' || $post['status'] == '6') {
			$dataNilai = [
				'row1_pemenuhan_k' => $post['row1_pemenuhan_k'],
				'row1_skor' => $post['row1_skor'],
				'row_2_perihal_1' => $post['row_2_perihal_1'],
				'row_2_perihal_2' => $post['row_2_perihal_2'],
				'row2_pemenuhan_k' => $post['row2_pemenuhan_k'],
				'row2_skor' => $post['row2_skor'],
				'row3_pemenuhan_k_tahun' => $post['row3_pemenuhan_k_tahun'],
				'row3_pemenuhan_k_bulan' => $post['row3_pemenuhan_k_bulan'],
				'row3_skor' => $post['row3_skor'],
				'row4_pemenuhan_k' => $post['row4_pemenuhan_k'],
				'row4_skor' => $post['row4_skor'],
				'row5_pemenuhan_k' => $post['row5_pemenuhan_k'],
				'row5_skor' => $post['row5_skor'],
				'h_sijab_id' => $id,
				// 'created_date' => date('Y-m-d H:i'),
			];

			$this->Sijab_model->save_nilai($dataNilai);
			// var_dump($this->db->last_query());



		}

		$file_cv = $this->generate_pdf_cv($id);
		$file_sijab = $this->generate_pdf_sijab($id);


		

		$file_image = (isset($_FILES['lampiran']) == TRUE ? $_FILES['lampiran'] : null); // ambil dahulu
		$config['upload_path'] = "assets/dokumen/sijab/" . $id . "/"; // lokasi folder yang akan digunakan untuk menyimpan file
		$config['allowed_types'] = 'jpg|JPG|jpeg|JPEG|PNG|png|pdf|PDF'; // extension yang diperbolehkan untuk diupload
		$config['file_name'] = 'thumb_new_lampiran_' . $id;
		$config['max_size'] = '5500';
		$config['overwrite'] = true;
		$this->upload->initialize($config);


		if (!$this->upload->do_upload('lampiran')) {
			$lampiran = NULL;
		} else { 
			$lampiran = 'assets/dokumen/sijab/' . $id . '/' . $this->upload->file_name;
		}

		$data_dokumen = [
			'url_cv' => $file_cv,
			'url_lampiran' => $lampiran,
		];

		$this->Sijab_model->update($data_dokumen, $id);


		$this->db->trans_complete();

		if ($this->db->trans_status()) {
			$this->session->set_flashdata('status', 'success');
			$this->session->set_flashdata('message', 'Data Calon Sidang Jabatan tersimpan');
		} else {
			$this->session->set_flashdata('status', 'danger');
			$this->session->set_flashdata('message', 'Data Calon Sidang Jabatan gagal tersimpan');
		}
		redirect('sijab');
	}

	public function ajax_load_sijab($status = NULL)
	{
		$post = $this->input->post();
		echo $this->Sijab_model->view_list($post, $status);
	}

	public function ajax_get_karyawan()
	{
		$pencarian = $this->input->post("q");

		if (empty($pencarian))
			$pencarian = "";
		return $this->Sijab_model->get_karyawan($pencarian);
	}

	public function ajax_get_karyawan_by_id($id)
    {
        $id = decode_url($id);
        $tmp=$this->Sijab_model->get_ajax_karyawan_by_id($id);
        return $tmp;
    }

	public function view_pdf($id)
	{
		$sijab = $this->Sijab_model->get_sijab_by_id($id);

		$data['detail'] = $sijab;
		// $data['m_karyawan'] = $m_karyawan;

		$namafile = url_title($sijab['nama'] . '-' . $sijab['nik']);

		$this->load->library('Pdf');
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR); //aplikasinya
		$pdf->SetAuthor('PT. TELKOM SATELIT INDONESIA');
		$pdf->SetTitle('Surat SIJAB - ' . $sijab['nama']);
		$pdf->SetSubject('Surat SIJAB');
		$pdf->setPageOrientation('L');


		//tidak memakai header	
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetFont('helvetica', 'false', 11, 'false');
		//set margin
		$pdf->SetMargins(10, 10, 10);
		// set auto page breaks
		// add a page
		$pdf->AddPage();


		$html = $this->load->view('sijab/view_pdf', $data, true);

		$path = './assets/dokumen/sijab/' . $id . '/CV.pdf';


		$data['pdf'] = base64_encode(file_get_contents($path));

		// $html = $this->load->view('sijab/preview_pdf', $data, true);

		// // $pdf->SetAutoPageBreak(true, 0);

		// $karyawan = $this->Sijab_model->get_detail_karyawan($sijab['m_karyawan_id']);
		// $m_karyawan = $this->Sijab_model->get_m_karyawan($sijab['m_karyawan_id']);
		// $data['detail_nilai'] = $this->Sijab_model->get_penilaian_sijab($sijab['id']);

		// $data['detail'] = $karyawan;
		// $data['m_karyawan'] = $m_karyawan;
		// $pdf->AddPage('P', 'LETTER');
		// $html .= $this->load->view('sijab/view_cv', $data, true);

		// output the HTML content
		$pdf->writeHTML($html, true, false, true, false, '');
		$pdf->Output($namafile . '.pdf', 'I');
	}

	public function view_dokumen($id)
	{
		
		$pdf = new PDFMerger;
		$path = './assets/dokumen/sijab/' . $id . '/';
		$pdf->addPDF($path.'SIJAB.pdf', 'all')
			->addPDF($path.'CV.pdf', 'all')

			->merge('brower', 'TEST.pdf');
	}

	public function generate_pdf_cv($id)
	{
		$sijab = $this->Sijab_model->get_sijab_by_id($id);

		$data['detail'] = $sijab;

		$namafile = url_title('CV-' . $sijab['nama'] . '-' . $sijab['nik']);

		$this->load->library('Pdf');
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR); //aplikasinya
		$pdf->SetAuthor('PT. TELKOM SATELIT INDONESIA');
		$pdf->SetTitle('CV SIJAB - ' . $sijab['nama']);
		$pdf->SetSubject('CV SIJAB');
		// $pdf->setPageOrientation('L');

		$sijab_folder =  FCPATH . "assets/dokumen/sijab/" . $sijab['id'] . "/";
		if (!is_dir($sijab_folder)) {
			mkdir($sijab_folder, 0757, true);
		}

		//tidak memakai header	
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetFont('helvetica', 'false', 11, 'false');
		//set margin
		$pdf->SetMargins(10, 10, 10);

		$karyawan = $this->Sijab_model->get_detail_karyawan($sijab['m_karyawan_id']);
		$m_karyawan = $this->Sijab_model->get_m_karyawan($sijab['m_karyawan_id']);
		$data['detail_nilai'] = $this->Sijab_model->get_penilaian_sijab($sijab['id']);

		$data['detail'] = $karyawan;
		$data['m_karyawan'] = $m_karyawan;
		$pdf->AddPage('P', 'LETTER');
		$html = $this->load->view('sijab/view_cv', $data, true);

		// output the HTML content
		$pdf->writeHTML($html, false, false, true, false, '');
		$pdf->Output($sijab_folder . $namafile . '.pdf', 'F');

		return $sijab_folder . $namafile . '.pdf';
	}

	public function generate_pdf_sijab($id)
	{
		$sijab = $this->Sijab_model->get_sijab_by_id($id);

		$data['detail'] = $sijab;

		$namafile = url_title('SIJAB' . $sijab['nama'] . '-' . $sijab['nik']);

		$this->load->library('Pdf');
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		$pdf->SetCreator(PDF_CREATOR); //aplikasinya
		$pdf->SetAuthor('PT. TELKOM SATELIT INDONESIA');
		$pdf->SetTitle('Surat SIJAB - ' . $sijab['nama']);
		$pdf->SetSubject('Surat SIJAB');
		$pdf->setPageOrientation('L');

		$sijab_folder =  FCPATH . "assets/dokumen/sijab/" . $sijab['id'] . "/";
		if (!is_dir($sijab_folder)) {
			mkdir($sijab_folder, 0757, true);
		}

		//tidak memakai header	
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetFont('helvetica', 'false', 11, 'false');
		//set margin
		$pdf->SetMargins(10, 10, 10);

		
		$pdf->AddPage();
		$html = $this->load->view('sijab/view_pdf', $data, true);

		// output the HTML content
		$pdf->writeHTML($html, false, false, true, false, '');
		$pdf->Output($sijab_folder . $namafile . '.pdf', 'F');

		return $sijab_folder . $namafile . '.pdf';
	}

	public function delete($id)
	{
		if (empty($id)) {

            $this->session->set_flashdata('status', 'danger');
            $this->session->set_flashdata('message', 'Anda Tidak bisa akses');
            redirect('sijab');

        } else {
            $result = $this->Sijab_model->delete($id);
			// if ($result == true) {
				$file = unlink(FCPATH .'assets/dokumen/sijab/'.$id.'/CV.pdf');
				unlink(FCPATH .'assets/dokumen/sijab/'.$id.'/SIJAB.pdf');
				rmdir(FCPATH .'assets/dokumen/sijab/'.$id);
			// }
            $this->session->set_flashdata('status', 'success');
            $this->session->set_flashdata('message', 'Data SIJAB telah dihapus');
            redirect('sijab');
        }
	}
	public function edit($id)
	{
		$data['css'] = array('plugins/datepicker/datepicker3.css', 'plugins/select2/select2.css', 'plugins/iCheck/all.css');
		$data['js'] = array(
			'js/jquery.validate.js', 'js/additional-methods.js', 'plugins/iCheck/icheck.min.js', 'plugins/datepicker/bootstrap-datepicker.js',
			'plugins/input-mask/jquery.inputmask.js', 'plugins/input-mask/jquery.inputmask.date.extensions.js', 'plugins/input-mask/jquery.inputmask.extensions.js', 'plugins/select2/select2.js', 'plugins/bootstrap-filestyle/src/bootstrap-filestyle.min.js',
		);
		$data['title'] = 'Edit Sijab';
		$data['icon'] = '<i class="mega-octicon octicon-person"></i>';
		
		// Get existing data
		$data['karyawan'] = $this->Sijab_model->get_sijab_by_id($id);
		$data['jenis_sijab'] = $this->Sijab_model->get_jenis_sijab();
		$data['jabatan'] = $this->jabatan_model->viewall()->result();
		$data['subdit'] = $this->organisasi_model->viewall()->result();
		$data['level'] = $this->Referensi_model->get_level_karyawan()->result();
		$data['penilaian'] = $this->Sijab_model->get_penilaian_sijab($id);

		$pdfCVURL = base_url('assets/dokumen/sijab/' . $id . '/' . $pdfCVFileName);
		$pdfSIJABURL = base_url('assets/dokumen/sijab/' . $id . '/' . $pdfSIJABFileName);

		$data['pdfCVURL'] = $pdfCVURL;
		$data['pdfSIJABURL'] = $pdfSIJABURL;

		$this->template->load('template', 'sijab/edit', $data);
		return $data;
	}

	public function update()
	{

		$this->db->trans_start();
		$post = $this->input->post();
		$id = $this->input->post('id');
		$nomor = '….../D0.000/HCM.01/TSAT/MM.YY';

		$data = [
			// 'nomor' => $nomor,
			'tgl' => $post['tgl'],
			'm_karyawan_id' => $post['m_karyawan_id'],
			'from_posisi' => $post['from_posisi'],
			'from_bp' => $post['from_bp'],
			'from_subdit' => $post['from_subdit'],
			'to_posisi' => $post['to_posisi'],
			'to_bp' => $post['to_bp'],
			'to_subdit' => $post['to_subdit'],
			'r_jenis_sijab_id' => $post['status'],
			'keterangan' => $post['keterangan'],
			'm_karyawan_id_ketua' => $post['m_karyawan_id_ketua'],
			'm_karyawan_id_anggota_1' => ($post['m_karyawan_id_anggota_1']) ? $post['m_karyawan_id_anggota_1'] : NULL,
			'm_karyawan_id_anggota_2' => ($post['m_karyawan_id_anggota_2']) ? $post['m_karyawan_id_anggota_2'] : NULL,
			'm_karyawan_id_anggota_3' => ($post['m_karyawan_id_anggota_3']) ? $post['m_karyawan_id_anggota_3'] : NULL,
			'm_karyawan_id_anggota_4' => ($post['m_karyawan_id_anggota_4']) ? $post['m_karyawan_id_anggota_4'] : NULL,
			'm_karyawan_id_sekretaris' => ($post['m_karyawan_id_sekretaris']) ? $post['m_karyawan_id_sekretaris'] : NULL,
			'status' => 'Draft',
			'created_by' => $this->biodata->id,
			'created_date' => date('Y-m-d H:i'),
		];

		$this->Sijab_model->update($data, $id);


		if ($post['status'] == '4' || $post['status'] == '6') {
			$dataNilai = [
				'row1_pemenuhan_k' => $post['row1_pemenuhan_k'],
				'row1_skor' => $post['row1_skor'],
				'row_2_perihal_1' => $post['row_2_perihal_1'],
				'row_2_perihal_2' => $post['row_2_perihal_2'],
				'row2_pemenuhan_k' => $post['row2_pemenuhan_k'],
				'row2_skor' => $post['row2_skor'],
				'row3_pemenuhan_k_tahun' => $post['row3_pemenuhan_k_tahun'],
				'row3_pemenuhan_k_bulan' => $post['row3_pemenuhan_k_bulan'],
				'row3_skor' => $post['row3_skor'],
				'row4_pemenuhan_k' => $post['row4_pemenuhan_k'],
				'row4_skor' => $post['row4_skor'],
				'row5_pemenuhan_k' => $post['row5_pemenuhan_k'],
				'row5_skor' => $post['row5_skor'],
				'h_sijab_id' => $id,
				// 'created_date' => date('Y-m-d H:i'),
			];

			$this->Sijab_model->save_nilai($dataNilai);
			// var_dump($this->db->last_query());



		}

		$file_cv = $this->generate_pdf_cv($id);
		$file_sijab = $this->generate_pdf_sijab($id);


		

		$file_image = (isset($_FILES['lampiran']) == TRUE ? $_FILES['lampiran'] : null); // ambil dahulu
		$config['upload_path'] = "assets/dokumen/sijab/" . $id . "/"; // lokasi folder yang akan digunakan untuk menyimpan file
		$config['allowed_types'] = 'jpg|JPG|jpeg|JPEG|PNG|png|pdf|PDF'; // extension yang diperbolehkan untuk diupload
		$config['file_name'] = 'thumb_new_lampiran_' . $id;
		$config['max_size'] = '5500';
		$config['overwrite'] = true;
		$this->upload->initialize($config);


		if (!$this->upload->do_upload('lampiran')) {
			$lampiran = $post['lampiran_lama'];
		} else {
			$lampiran = 'assets/dokumen/sijab/' . $id . '/' . $this->upload->file_name;
		}
		
		$data_dokumen = [
			'url_cv' => $file_cv,
			'url_lampiran' => $lampiran,
		];

		$this->Sijab_model->update($data_dokumen, $id);


		$this->db->trans_complete();

		if ($this->db->trans_status()) {
			$this->session->set_flashdata('status', 'success');
			$this->session->set_flashdata('message', 'Data Calon Sidang Jabatan terupdate');
		} else {
			$this->session->set_flashdata('status', 'danger');
			$this->session->set_flashdata('message', 'Data Calon Sidang Jabatan gagal terupdate');
		}
		redirect('sijab');	
	}

}
