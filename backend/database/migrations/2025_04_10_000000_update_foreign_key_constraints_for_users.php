<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Thêm khóa ngoại cho bảng rewritten_articles
        Schema::table('rewritten_articles', function (Blueprint $table) {
            // Kiểm tra sự tồn tại của khóa ngoại
            $foreignKeyExists = $this->foreignKeyExists('rewritten_articles', 'user_id');
            
            // Chỉ xóa khóa ngoại nếu nó tồn tại
            if ($foreignKeyExists) {
                $table->dropForeign(['user_id']);
            }
            
            // Thêm khóa ngoại mới với onDelete('cascade')
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
        
        // Thêm khóa ngoại cho bảng approved_articles
        Schema::table('approved_articles', function (Blueprint $table) {
            // Kiểm tra sự tồn tại của khóa ngoại
            $foreignKeyExists = $this->foreignKeyExists('approved_articles', 'user_id');
            
            // Chỉ xóa khóa ngoại nếu nó tồn tại
            if ($foreignKeyExists) {
                $table->dropForeign(['user_id']);
            }
            
            // Thêm khóa ngoại mới với onDelete('cascade')
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Khôi phục lại các khóa ngoại không có onDelete
        Schema::table('rewritten_articles', function (Blueprint $table) {
            $foreignKeyExists = $this->foreignKeyExists('rewritten_articles', 'user_id');
            
            if ($foreignKeyExists) {
                $table->dropForeign(['user_id']);
            }
            
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
        
        Schema::table('approved_articles', function (Blueprint $table) {
            $foreignKeyExists = $this->foreignKeyExists('approved_articles', 'user_id');
            
            if ($foreignKeyExists) {
                $table->dropForeign(['user_id']);
            }
            
            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
    }
    
    /**
     * Kiểm tra xem khóa ngoại có tồn tại hay không
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    protected function foreignKeyExists($table, $column)
    {
        $database = config('database.connections.mysql.database');
        
        $foreignKeys = DB::select(
            "SELECT * FROM information_schema.KEY_COLUMN_USAGE
             WHERE REFERENCED_TABLE_SCHEMA = ?
             AND TABLE_NAME = ?
             AND COLUMN_NAME = ?
             AND REFERENCED_TABLE_NAME IS NOT NULL",
            [$database, $table, $column]
        );
        
        return count($foreignKeys) > 0;
    }
}; 