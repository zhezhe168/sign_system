<?php

namespace app\attendquery\controller;

use app\common\controller\Common;
use app\adminquery\model\AdminModel;

use app\attendquery\model\AttendModel;
use app\classquery\model\ClassModel;

use app\logmanage\model\Log as LogModel;
use think\controller;
use think\Db;
use think\Request;
use think\Session;

class Allattend extends Common{
    public function index()
    {
        $att = new AttendModel();
        $class = new ClassModel();
        $admin = new AdminModel();

        $info = $att->getAllActAttend();
        foreach ($info as $key => $value) {
            $info[$key]['a_class'] = '';
            $info[$key]['a_creator_name'] = '';
            $ret = $class->getClassById($info[$key]['a_class_id']);
            if($ret){
                $info[$key]['a_class'] = $ret['c_name'];  # 祖组织单位名称
            }
            $ret = $admin->getNameByNum($info[$key]['a_creator']);
            if($ret){
                $info[$key]['a_creator_name'] = $ret['m_name'];  # 祖组织单位名称
            }
        }
        $this->assign('admin_id',Session::get('admin_id'));
        $this->assign('info',$info);


        // 获取当前用户的姓名和和班级信息传给前端
        $userInfo = $admin->getInfoByNum(Session::get('admin_id'));
        if($userInfo){
            $this->assign('userInfo', $userInfo);
        }
        return $this->fetch();
    }

    public function addAttend(){
        $data = input('post.');
        if (empty($data['a_name'])||empty($data['a_content'])||empty($data['a_start_time'])
            ||empty($data['a_end_time'])||empty($data['a_label'])){
            $this->error('输入不可为空');
        }
        $data['a_is_delete'] = 0;

        $att = new AttendModel();
        $ret = $att->addAttend($data);
        if ($ret){
            $this->success('添加成功');
        }else{
            $this->error('添加失败');
        }
    }

    public function delOneAttend(){
        $data = input('post.');
        $att = new AttendModel();
        $ret = $att->delOneAttend($data['a2s_id']);
        if($ret){
            $model = new LogModel();
            $uid = Session::get('admin_id');; // 操作人主键id，非学号
            $type = 4;
            $table = 'act2stu';
            $field =[$uid]; // 删除的主键列表, 不是学号
            $model->recordLogApi ($uid, $type, $table, $field); //需要判断调用是否成功

            
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }

    public function editAttend(){
        $data = input('post.');
        $act = new AttendModel();
        $ret = $act->editAttend($data);
        if($ret){
            $this->success('编辑成功！');
        }else{
            $this->error('编辑失败！');
        }
    }

    public function export(){
        //1.从数据库中取出数据
        echo "ddd";
        $attend = new AttendModel();
        // to do
        $list = $attend->getAllActAttend();
        echo "aaa";
        //2.加载PHPExcle类库
        vendor('PHPExcel.PHPExcel');
        //3.实例化PHPExcel类
        $objPHPExcel = new \PHPExcel();
        //4.激活当前的sheet表
        $objPHPExcel->setActiveSheetIndex(0);
        //5.设置表格头（即excel表格的第一行）
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '序号')
            ->setCellValue('B1', '活动ID')
            ->setCellValue('C1', '活动名称')
            ->setCellValue('D1', '活动地点')
            ->setCellValue('E1', '活动内容')
            ->setCellValue('F1', '活动标签')
            ->setCellValue('G1', '创建时间')
            ->setCellValue('H1', '开始时间')
            ->setCellValue('I1', '结束时间')
            ->setCellValue('J1', '举办年级')
            ->setCellValue('K1', '组织单位')
            ->setCellValue('L1', '创建人')
            ->setCellValue('M1', '创建人学号')
            ->setCellValue('N1', '参加人')
            ->setCellValue('O1', '参加人学号')
            ->setCellValue('P1', '开始签到')
            ->setCellValue('Q1', '结束签到')
            ->setCellValue('R1', '签到时间');

        //设置F列水平居中
        $objPHPExcel->setActiveSheetIndex(0)->getStyle('E')->getAlignment()
            ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //设置单元格宽度
        $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('C')->setWidth(15);
        $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('E')->setWidth(30);
        //6.循环刚取出来的数组，将数据逐一添加到excel表格。
        echo "aaa";
        $class = new ClassModel();
        $admin = new AdminModel();
        $log = array();
        for($i=0;$i<count($list);$i++){
            $item = $list[$i]['a2s_id'];
            $log[] = $item;
            $objPHPExcel->getActiveSheet()->setCellValue('A'.($i+2),$i+1);
            $objPHPExcel->getActiveSheet()->setCellValue('B'.($i+2),$list[$i]['a_id']);
            $objPHPExcel->getActiveSheet()->setCellValue('C'.($i+2),$list[$i]['a_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('D'.($i+2),$list[$i]['a_place']);
            $objPHPExcel->getActiveSheet()->setCellValue('E'.($i+2),$list[$i]['a_content']);
            $objPHPExcel->getActiveSheet()->setCellValue('F'.($i+2),$list[$i]['a_label']);
            $objPHPExcel->getActiveSheet()->setCellValue('G'.($i+2),$list[$i]['a_create_time']);
            $objPHPExcel->getActiveSheet()->setCellValue('H'.($i+2),$list[$i]['a_start_time']);
            $objPHPExcel->getActiveSheet()->setCellValue('I'.($i+2),$list[$i]['a_end_time']);
            $objPHPExcel->getActiveSheet()->setCellValue('J'.($i+2),$list[$i]['a_grade']);
            $objPHPExcel->getActiveSheet()->setCellValue('K'.($i+2),$class->getClassById($list[$i]['a_class_id'])['c_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('L'.($i+2),$admin->getNameByNum($list[$i]['a_creator'])['m_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('M'.($i+2),$list[$i]['a_creator']);
            $objPHPExcel->getActiveSheet()->setCellValue('N'.($i+2),$list[$i]['a2s_stu_name']);
            $objPHPExcel->getActiveSheet()->setCellValue('O'.($i+2),$list[$i]['a2s_stu_num']);
            $objPHPExcel->getActiveSheet()->setCellValue('P'.($i+2),$list[$i]['a_start_sign']);
            $objPHPExcel->getActiveSheet()->setCellValue('Q'.($i+2),$list[$i]['a_end_sign']);
            $objPHPExcel->getActiveSheet()->setCellValue('R'.($i+2),$list[$i]['a2s_sign_time']);

        }
        //7.设置保存的Excel表格名称
        $filename = '活动出席表'.date('ymd',time()).'.xls';
        //8.设置当前激活的sheet表格名称；
        $objPHPExcel->getActiveSheet()->setTitle('活动出席表');
        //9.设置浏览器窗口下载表格
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="'.$filename.'"');
        ob_end_clean();
        ob_start();
        //生成excel文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        //下载文件在浏览器窗口
        $objWriter->save('php://output');

        $model = new LogModel();
        $uid = Session::get('admin_id'); // 操作人主键id，非学号
        $type = 5;
        $table = 'act2stu';
        $field = $log;
        $ret = $model->recordLogApi($uid, $type, $table, $field); //需要判断调用是否成功
        if($ret) {
            echo "导出成功, 日志记录成功！";
            //$this->success('导出成功, 日志记录成功！');
        }else{
            echo "导出成功, 日志记录失败！";
            //$this->error('导出成功, 日志记录失败！');
        }
        exit;
    }
}