<?php
/*
Plugin Name: Content Toggle Plugin
Description: 自动隐藏文章中的"正确答案:"、"解析:"、"速记提示:"、"原文依据:"内容，并提供点击展开功能。
Version: 1.7
Author: Linker Lin ( https://jieyibu.net/ )
*/
// 定义需要处理的关键词及其对应的按钮文本
$ctp_keywords = array(
    '正确答案:' => '👀 正确答案 👀',
    '解析:' => '👀 解析 👀', 
    '速记提示:' => '👀 速记提示 👀',
    '原文依据:' => '👀 原文依据 👀'
);


if ( ! defined( 'ABSPATH' ) ) {
    exit; // 防止直接访问
}

// 添加必要的CSS和JS
function ctp_enqueue_scripts() {
    // 注册并排队CSS
    wp_register_style( 'ctp-style', false );
    wp_enqueue_style( 'ctp-style' );
    wp_add_inline_style( 'ctp-style', '
        .ctp-wrapper {
            margin-bottom: 15px;
        }
        .ctp-toggle {
            background-color: #f0f0f0;
            color: #0073aa;
            cursor: pointer;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            margin-bottom: 5px;
            display: inline-block;
        }
        .ctp-content {
            display: none;
            border-left: 3px solid #0073aa;
            padding-left: 10px;
        }
    ');

    // 注册并排队JavaScript
    wp_register_script( 'ctp-script', '', array(), '', true );
    wp_enqueue_script( 'ctp-script' );
    wp_add_inline_script( 'ctp-script', '
        document.addEventListener("DOMContentLoaded", function() {
            document.body.addEventListener("click", function(event) {
                if (event.target.classList.contains("ctp-toggle")) {
                    console.log("按钮点击了: ", event.target);
                    const wrapper = event.target.closest(".ctp-wrapper");
                    if (wrapper) {
                        const content = wrapper.querySelector(".ctp-content");
                        console.log("找到的内容元素: ", content);
                        if (content) {
                            if (content.style.display === "block") {
                                content.style.display = "none";
                                // 获取内容文本
                                const contentText = content.textContent.trim();
                                // 根据内容判断显示的文本
                                if(contentText.startsWith("解析：")) {
                                    event.target.textContent = "👀 解析 👀";
                                } else if(contentText.startsWith("速记提示：")) {
                                    event.target.textContent = "👀 速记提示 👀";  
                                } else if(contentText.startsWith("原文依据：")) {
                                    event.target.textContent = "👀 原文依据 👀";
                                } else if(contentText.startsWith("正确答案：")) {
                                    event.target.textContent = "👀 正确答案 👀";
                                } else {
                                    event.target.textContent = "显示内容";
                                }
                            } else {
                                content.style.display = "block";
                                event.target.textContent = "隐藏内容";
                            }
                        }
                    }
                }
            });
        });
    ');
}
add_action( 'wp_enqueue_scripts', 'ctp_enqueue_scripts' );

// 处理文章内容
function ctp_process_content($content) {
    // 定义要检测的关键词
    $patterns = array(
        '正确答案：',
        '原文依据：',
        '解析：',
        '速记提示：'
    );

    foreach ($patterns as $pattern) {
        // 匹配 pattern 后面到下一个换行或段落的内容
        $regex = '/(' . preg_quote($pattern, '/') . '(.*?))((?:\r?\n\r?\n|\Z))/s';

        // 替换成带按钮和隐藏内容的结构
        $replacement = '<div class="ctp-wrapper"><button class="ctp-toggle">显示内容</button><div class="ctp-content">$1</div></div>$3';

        $content = preg_replace($regex, $replacement, $content);
    }

    return $content;
}
add_filter('the_content', 'ctp_process_content');
?>
