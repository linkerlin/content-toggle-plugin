<?php
/*
Plugin Name: Content Toggle Plugin
Description: 自动隐藏文章中的"正确答案:"、"解析:"、"速记提示:"、"原文依据:"内容，并提供点击展开功能。支持选项点击判断答案。
Version: 1.8 
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
        .option-clickable {
            cursor: pointer;
            padding: 2px 5px;
            border-radius: 3px;
            transition: background-color 0.3s;
        }
        .option-selected {
            background-color: #ffeb3b;
        }
        .correct-mark {
            display: none;
        }
    ');

    // 注册并排队JavaScript
    wp_register_script( 'ctp-script', '', array(), '', true );
    wp_enqueue_script( 'ctp-script' );
    wp_add_inline_script( 'ctp-script', '
        document.addEventListener("DOMContentLoaded", function() {
            // 处理选项点击
            document.body.addEventListener("click", function(event) {
                if(event.target.classList.contains("option-clickable")) {
                    event.target.classList.toggle("option-selected");
                    
                    // 获取当前题目的所有选项
                    const questionDiv = event.target.closest(".question-wrapper");
                    if(!questionDiv) return;
                    
                    const selectedOptions = Array.from(questionDiv.querySelectorAll(".option-selected"))
                        .map(opt => opt.getAttribute("data-option"));
                    
                    // 获取正确答案
                    const answerDiv = questionDiv.querySelector(".ctp-content");
                    if(!answerDiv) return;
                    
                    const answerText = answerDiv.textContent;
                    const match = answerText.match(/正确答案：([A-Z]+)/);
                    if(!match) return;
                    
                    const correctAnswer = match[1].split("");
                    
                    // 判断答案是否正确
                    // if(selectedOptions.length === correctAnswer.length && 
                    //    selectedOptions.every(opt => correctAnswer.includes(opt))) {
                    //     // 答对了，显示所有隐藏内容
                    //     questionDiv.querySelectorAll(".ctp-content").forEach(content => {
                    //         content.style.display = "block";
                    //     });
                    //     questionDiv.querySelectorAll(".ctp-toggle").forEach(btn => {
                    //         btn.textContent = "隐藏内容";
                    //     });
                    // }
                }
                
                // 原有的切换按钮功能
                if (event.target.classList.contains("ctp-toggle")) {
                    const wrapper = event.target.closest(".ctp-wrapper");
                    if (wrapper) {
                        const content = wrapper.querySelector(".ctp-content");
                        if (content) {
                            if (content.style.display === "block") {
                                content.style.display = "none";
                                const contentText = content.textContent.trim();
                                if(contentText.startsWith("解析：")) {
                                    event.target.textContent = "👀 解析 👀";
                                } else if(contentText.startsWith("速记提示：")) {
                                    event.target.textContent = "👀 速记提示 👀";  
                                } else if(contentText.startsWith("原文依据：")) {
                                    event.target.textContent = "👀 原文依据 👀";
                                } else if(contentText.startsWith("正确答案：")) {
                                    event.target.textContent = "👀 正确答案 👀";
                                    // 隐藏正确答案标记
                                    const questionDiv = wrapper.closest(".question-wrapper");
                                    if(questionDiv) {
                                        const match = contentText.match(/正确答案：([A-Z]+)/);
                                        if(match) {
                                            const correctAnswers = match[1].split("");
                                            correctAnswers.forEach(answer => {
                                                const mark = questionDiv.querySelector(`[data-option="${answer}"] .correct-mark`);
                                                if(mark) mark.style.display = "none";
                                            });
                                        }
                                    }
                                } else {
                                    event.target.textContent = "显示内容";
                                }
                            } else {
                                content.style.display = "block";
                                event.target.textContent = "隐藏内容";
                                // 如果是正确答案，显示对应标记
                                const contentText = content.textContent.trim();
                                if(contentText.startsWith("正确答案：")) {
                                    const questionDiv = wrapper.closest(".question-wrapper");
                                    if(questionDiv) {
                                        const match = contentText.match(/正确答案：([A-Z]+)/);
                                        if(match) {
                                            const correctAnswers = match[1].split("");
                                            correctAnswers.forEach(answer => {
                                                const mark = questionDiv.querySelector(`[data-option="${answer}"] .correct-mark`);
                                                if(mark) mark.style.display = "inline";
                                            });
                                        }
                                    }
                                }
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
    // 将内容按题目分割
    $questions = preg_split('/<h[1-6][^>]*>.*?<\/h[1-6]>/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    
    foreach($questions as &$question) {
        // 处理选项,添加可点击效果
        $question = preg_replace('/([A-Z])\s*[.、]\s*([^<\n]+)/', 
            '<span class="option-clickable" data-option="$1">$1. $2<span class="correct-mark"> ✅</span></span>', 
            $question);
            
        // 处理隐藏内容
        $patterns = array(
            '正确答案：',
            '原文依据：',
            '解析：',
            '速记提示：'
        );

        foreach ($patterns as $pattern) {
            $regex = '/(' . preg_quote($pattern, '/') . '(.*?))((?:\r?\n\r?\n|\Z))/s';
            $replacement = '<div class="ctp-wrapper"><button class="ctp-toggle">显示内容</button><div class="ctp-content">$1</div></div>$3';
            $question = preg_replace($regex, $replacement, $question);
        }
        
        // 将整个题目包装在div中
        $question = '<div class="question-wrapper">' . $question . '</div>';
    }
    
    return implode('', $questions);
}
add_filter('the_content', 'ctp_process_content');
?>
