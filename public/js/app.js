(()=>{
  const q=(selector,root=document)=>root.querySelector(selector);
  const qa=(selector,root=document)=>[...root.querySelectorAll(selector)];

  const navToggle=q('[data-nav-toggle]');
  const nav=q('[data-nav]');
  navToggle?.addEventListener('click',()=>{
    const open=nav?.classList.toggle('open')||false;
    navToggle.setAttribute('aria-expanded',open?'true':'false');
  });

  qa('[data-nav] a').forEach(link=>link.addEventListener('click',()=>{
    nav?.classList.remove('open');
    navToggle?.setAttribute('aria-expanded','false');
  }));

  const closeToast=toast=>{
    if(!toast) return;
    toast.classList.add('toast-hide');
    window.setTimeout(()=>toast.remove(),220);
  };

  qa('[data-toast]').forEach(toast=>{
    q('[data-toast-close]',toast)?.addEventListener('click',()=>closeToast(toast));
    const timeout=Number(toast.dataset.timeout||0);
    if(timeout>0) window.setTimeout(()=>closeToast(toast),timeout);
  });

  const toastStack=q('.toast-stack');
  const processingToast=q('[data-processing-toast]');
  const processingTitle=q('[data-processing-title]',processingToast||document);
  let processingTimer=null;

  // Processing toast harus benar-benar hidden saat page load. CSS global juga
  // menghormati atribut [hidden] agar tidak kalah oleh .toast { display:flex }.
  if(processingToast){
    processingToast.hidden=true;
    processingToast.setAttribute('aria-hidden','true');
  }

  const showProcessing=(text='Memproses…',temporary=false)=>{
    if(!processingToast) return;
    if(processingTimer){window.clearTimeout(processingTimer);processingTimer=null;}
    if(processingTitle) processingTitle.textContent=text;
    processingToast.classList.remove('toast-hide');
    processingToast.hidden=false;
    processingToast.setAttribute('aria-hidden','false');
    if(temporary){
      processingTimer=window.setTimeout(()=>hideProcessing(),4500);
    }
  };

  const hideProcessing=()=>{
    if(!processingToast) return;
    if(processingTimer){window.clearTimeout(processingTimer);processingTimer=null;}
    processingToast.hidden=true;
    processingToast.setAttribute('aria-hidden','true');
  };

  const showClientToast=(message,type='success',timeout=4500)=>{
    if(!toastStack) return;
    const toast=document.createElement('div');
    toast.className=`toast ${type==='error'?'toast-error':'toast-success'}`;
    toast.dataset.toast='';
    const content=document.createElement('div');
    content.textContent=message;
    const button=document.createElement('button');
    button.type='button';
    button.className='toast-close';
    button.textContent='×';
    button.setAttribute('aria-label','Tutup notifikasi');
    button.addEventListener('click',()=>closeToast(toast));
    toast.append(content,button);
    toastStack.append(toast);
    window.setTimeout(()=>closeToast(toast),timeout);
  };

  // Required fields memakai indikator dan validasi GBUKR, bukan popup bawaan browser.
  const refreshRequiredMarks=(root=document)=>{
    qa('.required-star[data-auto-required]',root).forEach(star=>star.remove());
    qa('input[required],select[required],textarea[required]',root).forEach(control=>{
      if(control.type==='hidden') return;
      const field=control.closest('.field');
      const label=field?.querySelector('label') || control.closest('label') || (control.id ? q(`label[for="${CSS.escape(control.id)}"]`, root) : null);
      if(!label || label.querySelector('.required-star')) return;
      const star=document.createElement('span');
      star.className='required-star';
      star.dataset.autoRequired='1';
      star.textContent=' *';
      star.setAttribute('aria-hidden','true');
      label.append(star);
    });
  };

  const controlLabel=control=>{
    const label=control.closest('.field')?.querySelector('label') || control.closest('label') || (control.id ? q(`label[for="${CSS.escape(control.id)}"]`) : null);
    return (label?.childNodes?.[0]?.textContent||label?.textContent||control.getAttribute('aria-label')||control.name||'Field').replace('*','').trim();
  };

  const clientValidationMessage=control=>{
    const label=controlLabel(control);
    const validity=control.validity;
    if(validity.valueMissing) return `${label} wajib diisi.`;
    if(validity.typeMismatch && control.type==='email') return 'Masukkan alamat email yang valid.';
    if(validity.rangeUnderflow) return `${label} minimal ${control.min}.`;
    if(validity.rangeOverflow) return `${label} maksimal ${control.max}.`;
    if(validity.tooShort) return `${label} minimal ${control.minLength} karakter.`;
    if(validity.tooLong) return `${label} maksimal ${control.maxLength} karakter.`;
    if(validity.stepMismatch) return `${label} memiliki format angka yang tidak sesuai.`;
    if(validity.patternMismatch) return `${label} belum sesuai format yang diminta.`;
    if(validity.badInput) return `${label} belum diisi dengan format yang benar.`;
    return `${label} belum valid.`;
  };

  const clearFieldError=control=>{
    control.classList.remove('is-invalid');
    control.removeAttribute('aria-invalid');
    control.closest('.field')?.querySelector('.field-error[data-client-error]')?.remove();
  };

  const renderFieldError=control=>{
    clearFieldError(control);
    const field=control.closest('.field');
    const message=clientValidationMessage(control);
    control.classList.add('is-invalid');
    control.setAttribute('aria-invalid','true');
    if(field){
      const error=document.createElement('div');
      error.className='field-error';
      error.dataset.clientError='1';
      error.textContent=message;
      field.append(error);
    }
    return message;
  };

  qa('form').forEach(form=>{
    form.noValidate=true;
    qa('input,select,textarea',form).forEach(control=>{
      ['input','change'].forEach(eventName=>control.addEventListener(eventName,()=>{
        if(control.checkValidity()) clearFieldError(control);
      }));
    });
    form.addEventListener('submit',event=>{
      const invalid=qa('input,select,textarea',form).filter(control=>!control.disabled && !control.checkValidity());
      if(invalid.length===0) return;
      event.preventDefault();
      event.stopImmediatePropagation();
      const messages=invalid.map(renderFieldError);
      showClientToast(messages[0]||'Periksa kembali field yang wajib diisi.','error',6500);
      invalid[0]?.focus({preventScroll:true});
      invalid[0]?.scrollIntoView({behavior:'smooth',block:'center'});
    });
  });

  const productType=q('[data-product-type]');
  const closePo=q('[data-close-po]');
  const readyStockFields=qa('[data-required-when-ready]');
  const productCountry=q('[data-product-country]');
  const syncProductRequirements=()=>{
    if(!productType) return;
    const isPo=productType.value==='po';
    if(closePo) closePo.required=isPo;
    readyStockFields.forEach(control=>control.required=!isPo);
    refreshRequiredMarks();
  };
  const syncProductCurrencySymbol=()=>{
    if(!productCountry) return;
    const symbol=productCountry.selectedOptions?.[0]?.dataset.currencySymbol||'';
    qa('[data-foreign-price-label]').forEach(label=>label.textContent=`Harga mata uang asal (${symbol})`);
  };
  productType?.addEventListener('change',syncProductRequirements);
  productCountry?.addEventListener('change',syncProductCurrencySymbol);
  syncProductRequirements();
  syncProductCurrencySymbol();
  refreshRequiredMarks();

  qa('[data-bulk-adjustment-form]').forEach(form=>{
    const mode=q('[data-bulk-adjustment-mode]',form);
    const sharedWrap=q('[data-bulk-shared-amount]',form);
    const sharedInput=q('input[name="shared_amount_idr"]',form);
    const help=q('[data-bulk-amount-help]',form);
    const customHeading=q('[data-bulk-custom-heading]',form);
    const customCells=qa('[data-bulk-custom-cell]',form);
    const customInputs=qa('[data-bulk-custom-amount]',form);

    const sync=()=>{
      const isCustom=mode?.value==='custom';
      if(sharedWrap) sharedWrap.hidden=isCustom;
      if(sharedInput){ sharedInput.disabled=isCustom; sharedInput.required=!isCustom; }
      if(customHeading) customHeading.hidden=!isCustom;
      customCells.forEach(cell=>cell.hidden=!isCustom);
      customInputs.forEach(input=>{ input.disabled=!isCustom; input.required=isCustom && !!q(`input[name="order_ids[]"][value="${input.name.match(/\[(\d+)\]/)?.[1]}"]`,form)?.checked; });
      if(help && !isCustom) help.textContent=mode?.value==='per_item' ? 'Nominal akan dikali Qty pada masing-masing order.' : 'Nominal yang sama akan dibuat satu kali untuk setiap customer yang dipilih.';
      refreshRequiredMarks();
    };

    qa('input[name="order_ids[]"]',form).forEach(check=>check.addEventListener('change',sync));
    mode?.addEventListener('change',sync);
    sync();
  });

  const confirmDialog=q('#confirm-dialog');
  const confirmTitle=q('#confirm-title');
  const confirmMessage=q('#confirm-message');
  const confirmYes=q('#confirm-yes');
  const confirmNo=q('#confirm-no');
  let confirmAction=null;

  const openConfirm=(message,action,title='Konfirmasi')=>{
    if(!confirmDialog){action();return;}
    if(confirmTitle) confirmTitle.textContent=title;
    if(confirmMessage) confirmMessage.textContent=message||'Lanjutkan tindakan ini?';
    confirmAction=action;
    confirmDialog.showModal();
  };

  confirmYes?.addEventListener('click',()=>{
    const action=confirmAction;
    confirmAction=null;
    confirmDialog?.close();
    action?.();
  });

  confirmNo?.addEventListener('click',()=>{
    confirmAction=null;
    confirmDialog?.close();
  });

  qa('[data-dialog-close]').forEach(button=>button.addEventListener('click',()=>button.closest('dialog')?.close()));

  // Import legacy wajib memilih tepat satu tujuan GO sebelum request dikirim.
  qa('[data-go-choice-form]').forEach(form=>{
    const existing=q('[data-go-existing]',form);
    const fresh=q('[data-go-new]',form);
    const clear=()=>{
      [existing,fresh].forEach(control=>{
        if(!control) return;
        control.classList.remove('is-invalid');
        control.removeAttribute('aria-invalid');
      });
    };
    existing?.addEventListener('change',clear);
    fresh?.addEventListener('input',clear);
    form.addEventListener('submit',event=>{
      const hasExisting=!!existing?.value;
      const hasNew=!!fresh?.value.trim();
      if(hasExisting !== hasNew) return;

      event.preventDefault();
      event.stopImmediatePropagation();
      [existing,fresh].forEach(control=>{
        if(!control) return;
        control.classList.add('is-invalid');
        control.setAttribute('aria-invalid','true');
      });

      const message=hasExisting
        ? 'Pilih salah satu saja: gunakan GO yang sudah ada atau buat GO baru. Hapus salah satu pilihan sebelum melanjutkan.'
        : 'Import belum dijalankan karena GO belum ditentukan. Pilih GO yang sudah ada atau isi nama GO baru terlebih dahulu.';
      openConfirm(message,()=>{
        (hasExisting ? fresh : existing)?.focus();
      },hasExisting ? 'Pilih satu GO' : 'GO wajib dipilih');
    },true);
  });

  // Jika sebuah view lupa memberi konfirmasi pada DELETE, tetap paksa konfirmasi global.
  qa('form').forEach(form=>{
    const methodOverride=q('input[name="_method"]',form)?.value?.toUpperCase();
    if(methodOverride==='DELETE' && !form.dataset.confirm){
      form.dataset.confirm='Hapus data ini? Tindakan ini tidak dapat dibatalkan.';
    }
  });

  const dirtyForms=new Set();
  // Bulk checkbox helper untuk cleanup tabel.
  qa('[data-check-all]').forEach(master=>{
    const group=master.dataset.checkAll;
    const members=()=>qa(`[data-check-group="${group}"]:not(:disabled)`);
    master.addEventListener('change',()=>members().forEach(item=>{item.checked=master.checked;}));
    members().forEach(item=>item.addEventListener('change',()=>{
      const rows=members();
      master.checked=rows.length>0 && rows.every(row=>row.checked);
      master.indeterminate=rows.some(row=>row.checked) && !master.checked;
    }));
  });

  const unsavedToast=q('[data-unsaved-toast]');
  const syncUnsavedIndicator=()=>{
    if(!unsavedToast) return;
    const visible=dirtyForms.size>0;
    unsavedToast.hidden=!visible;
    unsavedToast.setAttribute('aria-hidden',visible?'false':'true');
  };

  const isMutationForm=form=>{
    const method=(form.getAttribute('method')||'get').toLowerCase();
    return method!=='get' && !form.hasAttribute('data-no-dirty-guard');
  };

  qa('form').forEach(form=>{
    if(!isMutationForm(form)) return;

    form.addEventListener('input',event=>{
      const target=event.target;
      if(target instanceof HTMLInputElement && ['hidden','submit','button'].includes(target.type)) return;
      dirtyForms.add(form);
      syncUnsavedIndicator();
    });

    form.addEventListener('change',event=>{
      const target=event.target;
      if(target instanceof HTMLInputElement && target.type==='hidden') return;
      dirtyForms.add(form);
      syncUnsavedIndicator();
    });
  });

  const otherDirtyForms=form=>[...dirtyForms].filter(item=>item!==form);

  // Satu handler konfirmasi untuk logout/destructive action dan untuk mencegah perubahan pada form lain hilang.
  qa('form').forEach(form=>form.addEventListener('submit',event=>{
    if(event.defaultPrevented || form.dataset.confirmed==='1') return;

    const otherDirty=otherDirtyForms(form);
    const explicitMessage=form.dataset.confirm||'';
    let message=explicitMessage;
    let title=form.dataset.confirmTitle||'Konfirmasi';

    if(otherDirty.length>0){
      const dirtyWarning='Ada perubahan pada form lain yang belum disimpan. Jika dilanjutkan, perubahan tersebut akan hilang.';
      message=message ? `${message} ${dirtyWarning}` : dirtyWarning;
      if(!explicitMessage) title='Perubahan belum disimpan';
    }

    if(!message) return;

    event.preventDefault();
    openConfirm(message,()=>{
      form.dataset.confirmed='1';
      if(otherDirty.length>0) dirtyForms.clear();
      else dirtyForms.delete(form);
      syncUnsavedIndicator();
      form.requestSubmit();
    },title);
  }));

  // Semua form mutasi biasa dikunci ketika submit untuk mencegah klik ganda dan record duplikat.
  qa('form').forEach(form=>form.addEventListener('submit',event=>{
    if(event.defaultPrevented) return;
    const method=(form.getAttribute('method')||'get').toLowerCase();
    if(method==='get' || form.hasAttribute('data-async-form')) return;

    if(form.dataset.submitting==='1'){
      event.preventDefault();
      return;
    }

    form.dataset.submitting='1';
    dirtyForms.delete(form);
    syncUnsavedIndicator();

    const buttons=qa('button[type="submit"],input[type="submit"]',form);
    buttons.forEach(button=>{
      button.disabled=true;
      if(button instanceof HTMLButtonElement){
        button.dataset.originalText=button.textContent||'';
        button.textContent=form.dataset.buttonLoadingText||'Memproses…';
      }
    });

    const temporary=form.hasAttribute('data-download-form');
    showProcessing(form.dataset.loadingText||'Memproses data…',temporary);

    if(temporary){
      window.setTimeout(()=>{
        form.dataset.submitting='0';
        buttons.forEach(button=>{
          button.disabled=false;
          if(button instanceof HTMLButtonElement && button.dataset.originalText){
            button.textContent=button.dataset.originalText;
          }
        });
        hideProcessing();
      },4500);
    }
  }));

  const shouldGuardLink=anchor=>{
    if(dirtyForms.size===0) return false;
    if(anchor.hasAttribute('download') || anchor.target==='_blank' || anchor.dataset.noDirtyGuard!==undefined) return false;
    const href=anchor.getAttribute('href');
    if(!href || href.startsWith('#') || href.startsWith('javascript:')) return false;
    try{
      const url=new URL(anchor.href,window.location.href);
      return url.origin===window.location.origin && url.href!==window.location.href;
    }catch(_){return false;}
  };

  document.addEventListener('click',event=>{
    const anchor=event.target.closest('a[href]');
    if(!anchor || !shouldGuardLink(anchor)) return;
    event.preventDefault();
    const destination=anchor.href;
    openConfirm('Ada perubahan yang belum disimpan. Tinggalkan halaman dan buang perubahan tersebut?',()=>{
      dirtyForms.clear();
      syncUnsavedIndicator();
      window.location.href=destination;
    },'Perubahan belum disimpan');
  });

  // Browser mengharuskan dialog native untuk refresh/close-tab. Internal navigation tetap memakai modal GBUKR.
  window.addEventListener('beforeunload',event=>{
    if(dirtyForms.size===0) return;
    event.preventDefault();
    event.returnValue='';
  });

  // Browser dapat mengembalikan halaman dari bfcache setelah submit/download.
  // Reset state UI agar toast loading dan tombol tidak tertinggal dalam kondisi aktif.
  const resetSubmitUi=()=>{
    hideProcessing();
    qa('form[data-submitting="1"]').forEach(form=>{
      form.dataset.submitting='0';
      qa('button[type="submit"],input[type="submit"]',form).forEach(button=>{
        button.disabled=false;
        if(button instanceof HTMLButtonElement && button.dataset.originalText){
          button.textContent=button.dataset.originalText;
        }
      });
    });
  };
  window.addEventListener('pageshow',resetSubmitUi);

  const proofInput=q('[data-proof-input]');
  const proofPreview=q('[data-proof-preview]');
  let proofObjectUrl=null;
  proofInput?.addEventListener('change',()=>{
    if(!proofPreview) return;
    if(proofObjectUrl){URL.revokeObjectURL(proofObjectUrl);proofObjectUrl=null;}
    proofPreview.replaceChildren();
    const file=proofInput.files?.[0];
    if(!file){proofPreview.hidden=true;return;}
    proofObjectUrl=URL.createObjectURL(file);
    const label=document.createElement('div');
    label.className='proof-preview-label';
    label.textContent=file.name;
    proofPreview.append(label);
    if(file.type.startsWith('image/')){
      const img=document.createElement('img');
      img.className='proof-image';
      img.src=proofObjectUrl;
      img.alt='Preview bukti transfer';
      proofPreview.append(img);
    }else if(file.type==='application/pdf'){
      const frame=document.createElement('iframe');
      frame.className='proof-frame proof-frame-small';
      frame.src=proofObjectUrl+'#toolbar=0';
      frame.title='Preview bukti transfer PDF';
      proofPreview.append(frame);
    }
    proofPreview.hidden=false;
  });


  // Semua input gambar mendapat preview otomatis.
  qa('input[type="file"][accept*="image"]').forEach(input=>{
    if(input.hasAttribute('data-proof-input')) return;
    let preview=input.parentElement?.querySelector('[data-image-preview]');
    if(!preview){preview=document.createElement('div');preview.className='image-input-preview';preview.dataset.imagePreview='1';preview.hidden=true;input.insertAdjacentElement('afterend',preview);}
    let objectUrl=null;
    input.addEventListener('change',()=>{
      if(objectUrl){URL.revokeObjectURL(objectUrl);objectUrl=null;}
      preview.replaceChildren();const file=input.files?.[0];
      if(!file){preview.hidden=true;return;}
      if(!file.type.startsWith('image/')){preview.hidden=true;return;}
      objectUrl=URL.createObjectURL(file);const img=document.createElement('img');img.src=objectUrl;img.alt='Preview gambar';preview.append(img);preview.hidden=false;
    });
  });

  // Field Tagihan Tambahan hanya tampil sesuai jenis yang dipilih.
  qa('[data-adjustment-form]').forEach(form=>{
    const reason=q('[data-adjustment-reason]',form);
    const sync=()=>{
      const value=reason?.value||'tax';
      const showWeight=['weight','weight_rate'].includes(value);
      const showRate=['rate','weight_rate'].includes(value);
      const showShipping=value==='shipping_actual';
      qa('[data-adjustment-field]',form).forEach(wrap=>{
        const type=wrap.dataset.adjustmentField;
        const show=(type==='weight'&&showWeight)||(type==='rate'&&showRate)||(type==='shipping'&&showShipping);
        wrap.hidden=!show;
        qa('input,select,textarea',wrap).forEach(control=>{control.disabled=!show;control.required=show && !control.name.startsWith('estimated_shipping');});
      });
      const notes=q('[data-adjustment-notes] textarea',form);if(notes) notes.required=value==='other';
      refreshRequiredMarks(form);
    };
    reason?.addEventListener('change',sync);sync();
  });

  const calc=q('#calculator-form');
  if(calc){
    calc.addEventListener('submit',async event=>{
      event.preventDefault();
      if(calc.dataset.submitting==='1') return;
      calc.dataset.submitting='1';

      const out=q('#calc-output');
      const button=q('button[type="submit"]',calc);
      button.disabled=true;
      const original=button.textContent;
      button.textContent='Menghitung…';
      showProcessing('Menghitung estimasi…');

      try{
        const res=await fetch(calc.action,{
          method:'POST',
          headers:{'Accept':'application/json','X-CSRF-TOKEN':q('input[name="_token"]',calc).value},
          body:new FormData(calc)
        });
        const data=await res.json();
        if(!res.ok) throw new Error(data.message||'Periksa input kalkulator.');
        q('#calc-total').textContent=new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(data.total_idr);
        q('#calc-detail').textContent=`Rate hari ini: ${new Intl.NumberFormat('id-ID',{maximumFractionDigits:4}).format(data.rate)} · Fee per barang: Rp${new Intl.NumberFormat('id-ID').format(data.fee_per_item_idr)}`;
        out.hidden=false;
      }catch(err){
        out.hidden=false;
        q('#calc-total').textContent='Belum dapat dihitung';
        q('#calc-detail').textContent=err.message;
      }finally{
        calc.dataset.submitting='0';
        button.disabled=false;
        button.textContent=original;
        hideProcessing();
      }
    });

    const country=q('#country_id',calc);
    const shipping=q('#shipping_option_id',calc);
    country?.addEventListener('change',()=>{
      const id=country.value;
      const symbol=country.selectedOptions?.[0]?.dataset.currencySymbol||'';
      const prefix=q('[data-currency-prefix]',calc);
      if(prefix) prefix.textContent=symbol;
      qa('option[data-country]',shipping).forEach(option=>option.hidden=option.dataset.country!==id);
      const first=qa('option[data-country]',shipping).find(option=>!option.hidden);
      shipping.value=first?.value||'';
    });
    country?.dispatchEvent(new Event('change'));
  }

  // Mini cart desktop: ringkasan cepat tanpa memaksa user meninggalkan halaman produk.
  qa('[data-cart-nav]').forEach(wrap=>{
    const pop=q('[data-cart-popover]',wrap);
    if(!pop) return;
    let loaded=false;
    const load=async()=>{
      pop.hidden=false;
      if(loaded) return;
      pop.innerHTML='<div class="small muted">Memuat keranjang…</div>';
      try{
        const res=await fetch(pop.dataset.url,{headers:{'Accept':'application/json'}});
        if(!res.ok) throw new Error('Gagal memuat');
        const data=await res.json();
        const esc=value=>String(value??'').replace(/[&<>"']/g,char=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
        const money=value=>'Rp'+new Intl.NumberFormat('id-ID').format(Number(value||0));
        const items=(data.items||[]).map(item=>`<div class="mini-cart-item"><div><strong>${esc(item.name)}</strong><div class="small muted">${esc(item.variant)} · ${item.qty} item</div></div><strong>${money(item.subtotal)}</strong></div>`).join('');
        pop.innerHTML=data.count>0 ? `<div class="mini-cart-list">${items}</div><div class="mini-cart-footer"><div><div class="small muted">${data.count} item</div><strong>${money(data.total)}</strong></div><a class="btn btn-primary btn-sm" href="/cart">Lihat Keranjang</a></div>` : '<div class="empty">Keranjang masih kosong.</div>';
        loaded=true;
      }catch(_){ pop.innerHTML='<div class="small muted">Ringkasan keranjang belum dapat dimuat.</div>'; }
    };
    wrap.addEventListener('mouseenter',load);
    wrap.addEventListener('mouseleave',()=>{pop.hidden=true;});
    wrap.addEventListener('focusin',load);
    wrap.addEventListener('focusout',event=>{if(!wrap.contains(event.relatedTarget)) pop.hidden=true;});
  });

  // Progress import background. Browser tidak menunggu proses XLSX besar selesai.
  qa('[data-import-run]').forEach(card=>{
    const url=card.dataset.statusUrl;
    const statusEl=q('.import-run-status',card);
    const bar=q('.import-progress-bar',card);
    const detail=q('.import-run-detail',card);
    if(!url || !statusEl || !bar || !detail) return;

    let currentState=String(statusEl.dataset.importStatus||'').toLowerCase();
    let stopped=['completed','failed','rolled_back'].includes(currentState);
    const poll=async()=>{
      if(stopped) return;
      try{
        const res=await fetch(url,{headers:{'Accept':'application/json'}});
        if(!res.ok) throw new Error('Status import tidak dapat dibaca.');
        const data=await res.json();
        currentState=String(data.status||'').toLowerCase();
        statusEl.dataset.importStatus=currentState;
        statusEl.textContent=String(data.status_label||'Sedang diproses');
        statusEl.className=`badge import-run-status ${currentState==='completed'?'ok':(currentState==='failed'?'danger':(currentState==='rolled_back'?'gray':'warn'))}`;
        bar.style.width=`${Number(data.progress||0)}%`;
        const base=`${new Intl.NumberFormat('id-ID').format(data.processed_rows||0)} / ${new Intl.NumberFormat('id-ID').format(data.total_rows||0)} baris`;
        if(currentState==='completed'){
          const s=data.summary||{};
          detail.textContent=`${base} · ${s.orders||0} order · ${s.invoices||0} tagihan · ${s.adjustments||0} kekurangan`;
          showClientToast('Import spreadsheet selesai.','success',6000);
          stopped=true;
        }else if(currentState==='failed'){
          detail.textContent=`${base} · ${data.error_message||'Import belum berhasil diselesaikan.'}`;
          showClientToast('Import belum berhasil. File asli tetap tersimpan dan dapat dicoba ulang.','error',7500);
          stopped=true;
        }else if(currentState==='rolled_back'){
          detail.textContent=`${base} · hasil import sudah dibersihkan`;
          stopped=true;
        }else{
          detail.textContent=base;
        }
      }catch(_){
        // Koneksi sementara gagal: coba lagi pada interval berikutnya.
      }
      if(!stopped) window.setTimeout(poll,2500);
    };
    if(!stopped) window.setTimeout(poll,1200);
  });
})();
