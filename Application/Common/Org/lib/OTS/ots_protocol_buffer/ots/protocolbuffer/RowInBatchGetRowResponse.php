<?php
namespace ots\protocolbuffer;

// @@protoc_insertion_point(namespace:.ots.protocolbuffer.RowInBatchGetRowResponse)

/**
 * Generated by the protocol buffer compiler.  DO NOT EDIT!
 * source: ots_protocol_buffer.proto
 *
 * -*- magic methods -*-
 *
 * @method bool getIsOk()
 * @method void setIsOk(bool $value)
 * @method \ots\protocolbuffer\Error getError()
 * @method void setError(\ots\protocolbuffer\Error $value)
 * @method \ots\protocolbuffer\ConsumedCapacity getConsumed()
 * @method void setConsumed(\ots\protocolbuffer\ConsumedCapacity $value)
 * @method \ots\protocolbuffer\Row getRow()
 * @method void setRow(\ots\protocolbuffer\Row $value)
 */
class RowInBatchGetRowResponse extends \ProtocolBuffers\Message
{
  // @@protoc_insertion_point(traits:.ots.protocolbuffer.RowInBatchGetRowResponse)
  
  /**
   * @var bool $is_ok
   * @tag 1
   * @label required
   * @type \ProtocolBuffers::TYPE_BOOL
   **/
  protected $is_ok;
  
  /**
   * @var \ots\protocolbuffer\Error $error
   * @tag 2
   * @label optional
   * @type \ProtocolBuffers::TYPE_MESSAGE
   **/
  protected $error;
  
  /**
   * @var \ots\protocolbuffer\ConsumedCapacity $consumed
   * @tag 3
   * @label optional
   * @type \ProtocolBuffers::TYPE_MESSAGE
   **/
  protected $consumed;
  
  /**
   * @var \ots\protocolbuffer\Row $row
   * @tag 4
   * @label optional
   * @type \ProtocolBuffers::TYPE_MESSAGE
   **/
  protected $row;
  
  
  // @@protoc_insertion_point(properties_scope:.ots.protocolbuffer.RowInBatchGetRowResponse)

  // @@protoc_insertion_point(class_scope:.ots.protocolbuffer.RowInBatchGetRowResponse)

  /**
   * get descriptor for protocol buffers
   * 
   * @return \ProtocolBuffersDescriptor
   */
  public static function getDescriptor()
  {
    static $descriptor;
    
    if (!isset($descriptor)) {
      $desc = new \ProtocolBuffers\DescriptorBuilder();
      $desc->addField(1, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_BOOL,
        "name"     => "is_ok",
        "required" => true,
        "optional" => false,
        "repeated" => false,
        "packable" => false,
        "default"  => true,
      )));
      $desc->addField(2, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_MESSAGE,
        "name"     => "error",
        "required" => false,
        "optional" => true,
        "repeated" => false,
        "packable" => false,
        "default"  => null,
        "message" => '\ots\protocolbuffer\Error',
      )));
      $desc->addField(3, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_MESSAGE,
        "name"     => "consumed",
        "required" => false,
        "optional" => true,
        "repeated" => false,
        "packable" => false,
        "default"  => null,
        "message" => '\ots\protocolbuffer\ConsumedCapacity',
      )));
      $desc->addField(4, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_MESSAGE,
        "name"     => "row",
        "required" => false,
        "optional" => true,
        "repeated" => false,
        "packable" => false,
        "default"  => null,
        "message" => '\ots\protocolbuffer\Row',
      )));
      // @@protoc_insertion_point(builder_scope:.ots.protocolbuffer.RowInBatchGetRowResponse)

      $descriptor = $desc->build();
    }
    return $descriptor;
  }

}